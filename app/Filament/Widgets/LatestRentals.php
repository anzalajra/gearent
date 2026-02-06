<?php

namespace App\Filament\Widgets;

use App\Models\Rental;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRentals extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Rental::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('rental_code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->label('Period')
                    ->formatStateUsing(fn (Rental $record) => $record->start_date->format('d M') . ' - ' . $record->end_date->format('d M Y'))
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('sm'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => Rental::getStatusColor($state))
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->toggleable()
                    ->visibleFrom('lg'),
            ])
            ->paginated(false);
    }
}