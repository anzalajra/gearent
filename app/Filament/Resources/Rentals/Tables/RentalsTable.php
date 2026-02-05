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
                            $record->load(['customer', 'items.productUnit.product', 'items.rentalItemKits.unitKit']);
                            
                            $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $record]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'Checklist-' . $record->rental_code . '.pdf'
                            );
                        }),

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
                        Rental::STATUS_PENDING,
                        Rental::STATUS_CANCELLED,
                        Rental::STATUS_COMPLETED,
                    ])),
            ])
            ->recordUrl(fn (Rental $record) => RentalResource::getUrl('view', ['record' => $record]))
            ->poll('30s');
    }
}