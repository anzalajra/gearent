<?php

namespace App\Filament\Resources\ProductUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->product->category->name ?? '-'),

                TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('condition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        'broken' => 'danger',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

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

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'scheduled' => 'Scheduled',
                        'rented' => 'Rented',
                        'maintenance' => 'Maintenance',
                        'retired' => 'Retired',
                    ]),
                SelectFilter::make('condition')
                    ->options([
                        'excellent' => 'Excellent',
                        'good' => 'Good',
                        'fair' => 'Fair',
                        'poor' => 'Poor',
                        'broken' => 'Broken',
                        'lost' => 'Lost',
                    ]),
                SelectFilter::make('category')
                    ->relationship('product.category', 'name')
                    ->label('Category')
                    ->searchable()
                    ->preload(),
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