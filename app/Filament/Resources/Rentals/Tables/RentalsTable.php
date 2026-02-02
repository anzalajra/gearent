<?php

namespace App\Filament\Resources\Rentals\Tables;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->filters([
                //
            ])
            ->recordActions([
                // Pickup button - only for pending/late_pickup
                Action::make('pickup')
                    ->label('Pickup')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn (Rental $record) => RentalResource::getUrl('pickup', ['record' => $record]))
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_LATE_PICKUP,
                    ])),

                // Return button - only for active/late_return
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->url(fn (Rental $record) => RentalResource::getUrl('return', ['record' => $record]))
                    ->visible(fn (Rental $record) => in_array($record->getRealTimeStatus(), [
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_RETURN,
                    ])),

                // Edit button - only for pending/late_pickup/completed/cancelled
                EditAction::make()
                    ->visible(fn (Rental $record) => $record->canBeEdited()),

                // Cancel button - only for pending/late_pickup
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
                    ->visible(fn (Rental $record) => $record->canBeCancelled()),

                // Delete button - only for pending/cancelled/completed
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
            ->toolbarActions([
                //
            ]);
    }
}