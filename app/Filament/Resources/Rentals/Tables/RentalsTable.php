<?php

namespace App\Filament\Resources\Rentals\Tables;

use App\Filament\Resources\Rentals\RentalResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Delivery;
use App\Models\Rental;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RentalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rental_code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
                TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('lg'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Rental::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'active' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        'late_pickup' => 'danger',
                        'late_return' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('sm'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Confirm Button
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Rental')
                    ->modalDescription('Are you sure you want to confirm this rental? This will change status to Confirmed and allow pickup.')
                    ->modalSubmitActionLabel('Yes, Confirm')
                    ->action(function (Rental $record) {
                        $record->update(['status' => Rental::STATUS_CONFIRMED]);
                        Notification::make()
                            ->title('Rental confirmed')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Rental $record) => $record->status === Rental::STATUS_PENDING),

                // Pickup button
                Action::make('pickup')
                    ->label('Pickup')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn (Rental $record) => RentalResource::getUrl('pickup', ['record' => $record]))
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_LATE_PICKUP,
                    ])),

                // Return button
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->url(fn (Rental $record) => RentalResource::getUrl('return', ['record' => $record]))
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_RETURN,
                    ])),

                // PDF Actions Group
                ActionGroup::make([
                    // In/Out Status (Delivery Documents)
                    Action::make('documents')
                        ->label('In/Out Status')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->url(fn (Rental $record) => RentalResource::getUrl('documents', ['record' => $record])),

                    // Checklist Form PDF
                    Action::make('download_checklist')
                        ->label('Download Checklist Form')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('gray')
                        ->action(function (Rental $record) {
                            $record->load(['customer', 'items.productUnit.product', 'items.productUnit.kits', 'items.rentalItemKits.unitKit']);
                            
                            $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $record]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Checklist-' . $record->rental_code . '.pdf'
                            );
                        }),

                    // Make Quotation
                    Action::make('make_quotation')
                        ->label('Make Quotation')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->action(function (Rental $record) {
                            $quotation = Quotation::create([
                                'customer_id' => $record->customer_id,
                                'date' => now(),
                                'valid_until' => now()->addDays(7),
                                'status' => Quotation::STATUS_ON_QUOTE,
                                'subtotal' => $record->subtotal,
                                'tax' => 0,
                                'total' => $record->total,
                                'notes' => $record->notes,
                            ]);

                            $record->update(['quotation_id' => $quotation->id]);

                            Notification::make()
                                ->title('Quotation created successfully')
                                ->success()
                                ->send();

                            return redirect()->to(QuotationResource::getUrl('edit', ['record' => $quotation]));
                        })
                        ->visible(function (Rental $record) {
                            // If invoice exists, do not show Make Quotation (level up)
                            if ($record->invoice_id) {
                                return false;
                            }

                            // Visible if NO quotation exists OR rental has been modified AFTER quotation creation
                            if (!$record->quotation_id) {
                                return true;
                            }
                            
                            $quotation = Quotation::find($record->quotation_id);
                            if (!$quotation) {
                                return true;
                            }

                            // Check if rental updated_at is greater than quotation created_at
                            // Using a small buffer (e.g. 1 minute) to avoid immediate re-show
                            return $record->updated_at->gt($quotation->created_at->addMinutes(1));
                        }),

                    // Download Quotation
                    Action::make('download_quotation')
                        ->label('Download Quotation')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->action(function (Rental $record) {
                            $quotation = Quotation::with(['customer', 'rentals.items.productUnit.product', 'rentals.items.rentalItemKits.unitKit'])->find($record->quotation_id);
                            
                            if (!$quotation) {
                                Notification::make()
                                    ->title('Quotation not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Quotation-' . $quotation->number . '.pdf'
                            );
                        })
                        ->visible(function (Rental $record) {
                            // If invoice exists, do not show Download Quotation (level up)
                            if ($record->invoice_id) {
                                return false;
                            }

                            // Visible if quotation exists AND (rental NOT modified OR Make Quotation is hidden)
                            // Actually, just "Visible if quotation exists" is simpler, but user said "buttons change".
                            // If "Make Quotation" is visible, "Download" should probably be hidden?
                            // User said: "kalau rental sudah terdeteksi ada di quotation, tidak bisa buat quotation lagi... semua tombol ganti jadi 'download quotation'"
                            // AND "kecuali rental ada edit... quotation bisa dibuat lagi".
                            // So if "Make" is visible, "Download" should be hidden.
                            
                            if (!$record->quotation_id) {
                                return false;
                            }

                            $quotation = Quotation::find($record->quotation_id);
                            if (!$quotation) {
                                return false;
                            }

                            // If rental modified after quotation, show "Make", hide "Download"?
                            // Or show both? User said "ganti jadi", implies swap.
                            return !$record->updated_at->gt($quotation->created_at->addMinutes(1));
                        }),

                    // Download Invoice
                    Action::make('download_invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('gray')
                        ->action(function (Rental $record) {
                            $invoice = \App\Models\Invoice::with(['customer', 'rentals.items.productUnit.product', 'rentals.items.rentalItemKits.unitKit'])->find($record->invoice_id);
                            
                            if (!$invoice) {
                                Notification::make()
                                    ->title('Invoice not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Invoice-' . $invoice->number . '.pdf'
                            );
                        })
                        ->visible(fn (Rental $record) => !empty($record->invoice_id)),
                ]),

                // Edit button
                EditAction::make()
                    ->visible(fn (Rental $record) => $record->canBeEdited()),

                // Cancel button
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Rental')
                    ->modalDescription('Are you sure you want to cancel this rental?')
                    ->form([
                        Textarea::make('cancel_reason')
                            ->label('Reason for cancellation')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Rental $record, array $data) {
                        $record->cancelRental($data['cancel_reason']);

                        Notification::make()
                            ->title('Rental cancelled')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_LATE_PICKUP,
                    ])),

                // Delete button
                DeleteAction::make()
                    ->visible(fn (Rental $record) => in_array($record->status, [
                        Rental::STATUS_CANCELLED,
                        Rental::STATUS_COMPLETED,
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Rental $record) => RentalResource::getUrl('view', ['record' => $record]))
            ->poll('30s');
    }
}
