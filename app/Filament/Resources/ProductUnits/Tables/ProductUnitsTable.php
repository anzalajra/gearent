<?php

namespace App\Filament\Resources\ProductUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('condition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                    })
                    ->toggleable()
                    ->visibleFrom('sm'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'scheduled' => 'primary',
                        'rented' => 'warning',
                        'maintenance' => 'info',
                        'retired' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('purchase_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('purchase_price')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('current_value')
                    ->label('Current Value')
                    ->money('IDR')
                    ->getStateUsing(fn ($record) => $record->current_value)
                    ->sortable(false)
                    ->toggleable()
                    ->visibleFrom('lg')
                    ->helperText('Depreciated Value'),

                TextColumn::make('profitability')
                    ->label('Profit/Loss')
                    ->money('IDR')
                    ->getStateUsing(fn ($record) => $record->calculateProfitability())
                    ->color(fn (string $state): string => $state >= 0 ? 'success' : 'danger')
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->helperText('Rev - Maint - Cost'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}