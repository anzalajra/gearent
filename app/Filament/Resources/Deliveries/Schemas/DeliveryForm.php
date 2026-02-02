<?php

namespace App\Filament\Resources\Deliveries\Schemas;

use App\Models\Delivery;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery Information')
                    ->schema([
                        TextInput::make('delivery_number')
                            ->label('Delivery Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Select::make('rental_id')
                            ->label('Rental')
                            ->relationship('rental', 'rental_code')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('items', [])),

                        Select::make('type')
                            ->label('Type')
                            ->options(Delivery::getTypeOptions())
                            ->required()
                            ->live(),

                        DatePicker::make('date')
                            ->label('Date')
                            ->default(now())
                            ->required(),

                        Select::make('checked_by')
                            ->label('Checked By')
                            ->relationship('checkedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id()),

                        Select::make('status')
                            ->label('Status')
                            ->options(Delivery::getStatusOptions())
                            ->default('draft')
                            ->disabled(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}