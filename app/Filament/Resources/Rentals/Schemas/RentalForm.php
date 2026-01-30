<?php

namespace App\Filament\Resources\Rentals\Schemas;

use App\Models\Customer;
use App\Models\ProductUnit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RentalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rental_code')
                    ->label('Rental Code')
                    ->default('AUTO')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->default(now()),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->required()
                    ->default(now()->addDays(1)),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),

                Repeater::make('items')
                    ->label('Rental Items')
                    ->relationship()
                    ->schema([
                        Select::make('product_unit_id')
                            ->label('Product Unit')
                            ->options(
                                ProductUnit::where('status', 'available')
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(fn ($unit) => [
                                        $unit->id => $unit->product->name . ' - ' . $unit->serial_number
                                    ])
                            )
                            ->required()
                            ->searchable()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                        TextInput::make('daily_rate')
                            ->label('Daily Rate')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0),

                        TextInput::make('days')
                            ->label('Days')
                            ->numeric()
                            ->required()
                            ->default(1),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(0),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->defaultItems(1),

                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                TextInput::make('discount')
                    ->label('Discount')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                TextInput::make('deposit')
                    ->label('Deposit')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}