<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true)
                    ->label('Active')
                    ->hiddenOn('edit'),
                Toggle::make('is_available_for_rental')
                    ->default(true)
                    ->label('Available for Rental')
                    ->helperText('If disabled, units in this warehouse cannot be rented.')
                    ->hiddenOn('edit'),
            ]);
    }
}
