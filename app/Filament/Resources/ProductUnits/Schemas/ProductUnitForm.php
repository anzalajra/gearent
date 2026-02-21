<?php

namespace App\Filament\Resources\ProductUnits\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select Warehouse')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('location'),
                    ]),

                TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('SN-A7IV-001'),

                Select::make('condition')
                    ->options(\App\Models\ProductUnit::getConditionOptions())
                    ->required()
                    ->default('excellent'),

                Select::make('status')
                    ->options(\App\Models\ProductUnit::getStatusOptions())
                    ->required()
                    ->default('available'),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('purchase_date')
                    ->label('Purchase Date'),

                TextInput::make('purchase_price')
                    ->label('Purchase Price')
                    ->numeric()
                    ->prefix('Rp'),

                TextInput::make('residual_value')
                    ->label('Residual Value')
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Estimated value at end of life'),

                TextInput::make('useful_life')
                    ->label('Useful Life (Months)')
                    ->numeric()
                    ->default(60)
                    ->suffix('months'),
            ]);
    }
}