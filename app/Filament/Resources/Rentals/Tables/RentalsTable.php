<?php

namespace App\Filament\Resources\Rentals\Tables;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Delivery;
use App\Models\Rental;
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
                    ->label('Rental Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn (Rental $record): string => $record->getRealTimeStatus())
                    ->color(fn (string $state): string => Rental::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                // Pickup button
                Action::make('pickup')
                    ->label('Pickup')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn (Rental $record) => RentalResource::getUrl('pickup', ['record' => $record]))
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_PENDING,
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

                // Documents dropdown
                ActionGroup::make([
                    // Quotation PDF
                    Action::make('download_quotation')
                        ->label('Download Quotation')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->action(function (Rental $record) {
                            $record->load(['customer', 'items.productUnit.product']);
                            
                            $pdf = Pdf::loadView('pdf.quotation', ['rental' => $record]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Quotation-' . $record->rental_code . '.pdf'
                            );
                        }),

                    // Invoice PDF
                    Action::make('download_invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('gray')
                        ->action(function (Rental $record) {
                            $record->load(['customer', 'items.productUnit.product']);
                            
                            $pdf = Pdf::loadView('pdf.invoice', ['rental' => $record]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Invoice-' . $record->rental_code . '.pdf'
                            );
                        }),

                    // Create Delivery Out
                    Action::make('create_delivery_out')
                        ->label('Create Surat Jalan Keluar')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Create Surat Jalan Keluar')
                        ->modalDescription('This will create a new delivery document for check-out.')
                        ->action(function (Rental $record) {
                            $existing = $record->deliveries()->where('type', 'out')->first();
                            if ($existing) {
                                Notification::make()
                                    ->title('Surat Jalan Keluar already exists')
                                    ->body('Delivery number: ' . $existing->delivery_number)
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $delivery = Delivery::create([
                                'rental_id' => $record->getKey(),
                                'type' => 'out',
                                'date' => now(),
                                'checked_by' => Auth::id(),
                                'status' => 'draft',
                            ]);

                            foreach ($record->items as $rentalItem) {
                                $delivery->items()->create([
                                    'rental_item_id' => $rentalItem->getKey(),
                                    'rental_item_kit_id' => null,
                                    'is_checked' => false,
                                ]);

                                foreach ($rentalItem->rentalItemKits as $kit) {
                                    $delivery->items()->create([
                                        'rental_item_id' => $rentalItem->getKey(),
                                        'rental_item_kit_id' => $kit->getKey(),
                                        'is_checked' => false,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Surat Jalan Keluar created')
                                ->body('Delivery number: ' . $delivery->delivery_number)
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                            Rental::STATUS_PENDING,
                            Rental::STATUS_LATE_PICKUP,
                            Rental::STATUS_ACTIVE,
                        ]) && !$record->deliveries()->where('type', 'out')->exists()),

                    // Create Delivery In
                    Action::make('create_delivery_in')
                        ->label('Create Surat Jalan Masuk')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Create Surat Jalan Masuk')
                        ->modalDescription('This will create a new delivery document for check-in.')
                        ->action(function (Rental $record) {
                            $existing = $record->deliveries()->where('type', 'in')->first();
                            if ($existing) {
                                Notification::make()
                                    ->title('Surat Jalan Masuk already exists')
                                    ->body('Delivery number: ' . $existing->delivery_number)
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $delivery = Delivery::create([
                                'rental_id' => $record->getKey(),
                                'type' => 'in',
                                'date' => now(),
                                'checked_by' => Auth::id(),
                                'status' => 'draft',
                            ]);

                            foreach ($record->items as $rentalItem) {
                                $delivery->items()->create([
                                    'rental_item_id' => $rentalItem->getKey(),
                                    'rental_item_kit_id' => null,
                                    'is_checked' => false,
                                ]);

                                foreach ($rentalItem->rentalItemKits as $kit) {
                                    $delivery->items()->create([
                                        'rental_item_id' => $rentalItem->getKey(),
                                        'rental_item_kit_id' => $kit->getKey(),
                                        'is_checked' => false,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Surat Jalan Masuk created')
                                ->body('Delivery number: ' . $delivery->delivery_number)
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                            Rental::STATUS_ACTIVE,
                            Rental::STATUS_LATE_RETURN,
                        ]) && !$record->deliveries()->where('type', 'in')->exists()),
                ])
                ->label('Documents')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->button(),

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
                    ->visible(fn (Rental $record) => $record->canBeDeleted()),
            ])
            ->recordUrl(function (Rental $record) {
                $status = $record->getRealTimeStatus();

                return match (true) {
                    in_array($status, [Rental::STATUS_PENDING, Rental::STATUS_LATE_PICKUP]) 
                        => RentalResource::getUrl('pickup', ['record' => $record]),
                    in_array($status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]) 
                        => RentalResource::getUrl('return', ['record' => $record]),
                    in_array($status, [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED]) 
                        => RentalResource::getUrl('view', ['record' => $record]),
                    default => null,
                };
            })
            ->poll('30s');
    }
}