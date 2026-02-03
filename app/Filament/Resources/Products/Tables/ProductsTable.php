<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 1,
                'xl' => 2,
            ])
            ->columns([
                \Filament\Tables\Columns\Layout\Grid::make(1)
                    ->schema([
                        \Filament\Tables\Columns\Layout\Split::make([
                            \Filament\Tables\Columns\Layout\Grid::make(1)
                                ->schema([
                                    ImageColumn::make('image')
                                        ->square()
                                        ->size(180)
                                        ->extraImgAttributes([
                                            'class' => 'object-cover w-full h-full rounded-l-xl',
                                        ]),
                                ])
                                ->grow(false),

                            \Filament\Tables\Columns\Layout\Stack::make([
                                TextColumn::make('name')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->icon('heroicon-m-cube')
                                    ->searchable(),

                                TextColumn::make('daily_rate')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.') . ' / 1 Day')
                                    ->color('gray')
                                    ->size('sm'),

                                \Filament\Tables\Columns\Layout\Split::make([
                                    TextColumn::make('units_count')
                                        ->counts('units')
                                        ->icon('heroicon-m-rectangle-stack')
                                        ->formatStateUsing(fn($state) => "Unit: {$state}")
                                        ->color('primary')
                                        ->size('xs'),

                                    TextColumn::make('available_units_count')
                                        ->getStateUsing(fn($record) => $record->units()->where('status', 'available')->count())
                                        ->icon('heroicon-m-check-circle')
                                        ->formatStateUsing(fn($state) => "Stock: {$state}")
                                        ->color('success')
                                        ->size('xs'),
                                ])->extraAttributes(['class' => 'max-w-max gap-4']),

                                TextColumn::make('edit_button')
                                    ->label('')
                                    ->default('Edit Product')
                                    ->formatStateUsing(fn() => view('filament.components.edit-button-link'))
                                    ->extraAttributes(['class' => 'mt-auto pt-4']),
                            ])
                            ->grow()
                            ->space(2)
                            ->extraAttributes(['class' => 'p-6']),
                        ])
                        ->extraAttributes([
                            'class' => 'bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden flex items-stretch',
                        ]),
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->recordUrl(fn($record) => \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
