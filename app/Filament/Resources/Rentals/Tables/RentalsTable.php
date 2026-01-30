<?php

namespace App\Filament\Resources\Rentals\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                    }),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('deposit')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Start Rental Action
                Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Start Rental')
                    ->modalDescription('This will mark the rental as active and set all product units to "rented". Continue?')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->markAsActive();
                        
                        Notification::make()
                            ->title('Rental Started!')
                            ->body('Product units are now marked as rented.')
                            ->success()
                            ->send();
                    }),

                // Complete Rental Action
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-m-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Rental')
                    ->modalDescription('This will mark the rental as completed and return all product units to "available". Continue?')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $record->markAsCompleted();
                        
                        Notification::make()
                            ->title('Rental Completed!')
                            ->body('Product units are now available again.')
                            ->success()
                            ->send();
                    }),

                // Cancel Rental Action
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Rental')
                    ->modalDescription('This will cancel the rental and return all product units to "available". Continue?')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'active']))
                    ->action(function ($record) {
                        $record->markAsCancelled();
                        
                        Notification::make()
                            ->title('Rental Cancelled!')
                            ->body('Product units are now available again.')
                            ->warning()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}