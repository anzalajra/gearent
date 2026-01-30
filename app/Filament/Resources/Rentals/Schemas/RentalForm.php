<?php

namespace App\Filament\Resources\Rentals\Schemas;

use App\Models\Customer;
use App\Models\ProductUnit;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Carbon\Carbon;

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

                DateTimePicker::make('start_date')
                    ->label('Start Date & Time')
                    ->required()
                    ->default(now())
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        self::calculateDuration($get, $set);
                    }),

                DateTimePicker::make('end_date')
                    ->label('End Date & Time')
                    ->required()
                    ->default(now()->addDays(1))
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        self::calculateDuration($get, $set);
                    }),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),

                Placeholder::make('duration_display')
                    ->label('Rental Duration')
                    ->content(function (callable $get) {
                        $startDate = $get('start_date');
                        $endDate = $get('end_date');

                        if ($startDate && $endDate) {
                            $start = Carbon::parse($startDate);
                            $end = Carbon::parse($endDate);
                            
                            $totalHours = (int) $start->diffInHours($end);
                            $days = (int) floor($totalHours / 24);
                            $hours = $totalHours % 24;

                            if ($days > 0 && $hours > 0) {
                                return "📅 {$days} hari {$hours} jam";
                            } elseif ($days > 0) {
                                return "📅 {$days} hari";
                            } else {
                                return "📅 {$hours} jam";
                            }
                        }

                        return '-';
                    })
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->label('Rental Items')
                    ->relationship()
                    ->schema([
                        Select::make('product_unit_id')
                            ->label('Product Unit')
                            ->options(function () {
                                return ProductUnit::where('status', 'available')
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(function ($unit) {
                                        return [$unit->id => $unit->product->name . ' - ' . $unit->serial_number];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if ($state) {
                                    $unit = ProductUnit::with('product')->find($state);
                                    if ($unit) {
                                        $set('daily_rate', $unit->product->daily_rate);
                                        
                                        // Auto-set days from dates
                                        $startDate = $get('../../start_date');
                                        $endDate = $get('../../end_date');
                                        
                                        if ($startDate && $endDate) {
                                            $start = Carbon::parse($startDate);
                                            $end = Carbon::parse($endDate);
                                            $days = max(1, (int) ceil($start->diffInHours($end) / 24));
                                            $set('days', $days);
                                            $set('subtotal', $unit->product->daily_rate * $days);
                                        }
                                    }
                                }
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                        TextInput::make('daily_rate')
                            ->label('Daily Rate')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $days = (int) ($get('days') ?? 1);
                                $set('subtotal', (float) $state * $days);
                            }),

                        TextInput::make('days')
                            ->label('Days')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $dailyRate = (float) ($get('daily_rate') ?? 0);
                                $set('subtotal', $dailyRate * (int) $state);
                            }),

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
                    ->default(0)
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('discount')
                    ->label('Discount')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(true),

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

    // Calculate duration and update days in items
    public static function calculateDuration(callable $get, callable $set): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $days = max(1, (int) ceil($start->diffInHours($end) / 24));

            // Update days in all items
            $items = $get('items') ?? [];
            foreach ($items as $key => $item) {
                $set("items.{$key}.days", $days);
                $dailyRate = (float) ($item['daily_rate'] ?? 0);
                $set("items.{$key}.subtotal", $dailyRate * $days);
            }
        }
    }
}