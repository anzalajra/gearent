<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Rental;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduledRentalsRelationManager extends RelationManager
{
    protected static string $relationship = 'rentalItems';

    protected static ?string $title = 'Scheduled Rentals';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rental.rental_code')
                    ->label('Rental Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rental.customer.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('productUnit.serial_number')
                    ->label('Unit SN')
                    ->searchable(),

                TextColumn::make('rental.start_date')
                    ->label('Start Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('rental.end_date')
                    ->label('End Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('rental.status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record): string => $record->rental->getRealTimeStatus())
                    ->color(fn (string $state): string => Rental::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
            ])
            ->defaultSort('rental.start_date', 'desc')
            ->filters([
                //
            ]);
    }
}
