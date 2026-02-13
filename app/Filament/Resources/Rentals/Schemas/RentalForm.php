<?php

namespace App\Filament\Resources\Rentals\Schemas;

use App\Models\User;
use App\Models\ProductUnit;
use App\Models\Rental;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class RentalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('id')->dehydrated(false),
                TextInput::make('rental_code')
                    ->label('Rental Code')
                    ->default('AUTO')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('user_id')
                    ->label('Customer')
                    ->options(User::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->disabled(fn ($record) => $record && in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN])),

                DateTimePicker::make('start_date')
                ->label('Start Date & Time')
                ->required()
                ->native(false)
                ->default(now())
                ->seconds(false)
                ->live()
                ->disabled(fn ($record) => $record && in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]))
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::calculateDuration($get, $set);
                }),

                DateTimePicker::make('end_date')
                ->label('End Date & Time')
                ->required()
                ->native(false)
                ->default(now()->addDays(1))
                ->seconds(false)
                ->live()
                ->disabled(fn ($record) => $record && in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]))
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    self::calculateDuration($get, $set);
                }),
                Select::make('status')
                    ->options(Rental::getStatusOptions())
                    ->required()
                    ->default('pending')
                    ->disabled(fn ($record) => $record && (!$record->canBeEdited() || in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]))),

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
                    ->columns(12)
                    ->addable(false)
                    ->disabled(fn ($record) => $record && in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]))
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(function () {
                                $products = \App\Models\Product::with('variations')->where('is_active', true)->get();
                                $options = [];
                                foreach ($products as $product) {
                                    if ($product->variations->isNotEmpty()) {
                                        foreach ($product->variations as $variation) {
                                            $key = "{$product->id}:{$variation->id}";
                                            $label = "{$product->name} ({$variation->name})";
                                            $options[$key] = $label;
                                        }
                                    } else {
                                        $options[$product->id] = $product->name;
                                    }
                                }
                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpan(4)
                            ->dehydrated(false) 
                            ->afterStateUpdated(function (callable $set) {
                                $set('product_unit_id', null);
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->productUnit) {
                                    if ($record->productUnit->product_variation_id) {
                                        $component->state("{$record->productUnit->product_id}:{$record->productUnit->product_variation_id}");
                                    } else {
                                        $component->state($record->productUnit->product_id);
                                    }
                                }
                            }),

                        Select::make('product_unit_id')
                            ->label('Unit')
                            ->options(function (callable $get) {
                                $startDate = $get('../../start_date');
                                $endDate = $get('../../end_date');
                                $currentRentalId = $get('../../id');
                                $productCompositeId = $get('product_id');
                                
                                $productId = $productCompositeId;
                                $variationId = null;
                                
                                if ($productCompositeId && str_contains($productCompositeId, ':')) {
                                    [$productId, $variationId] = explode(':', $productCompositeId);
                                }

                                return self::getAvailableUnits($startDate, $endDate, $currentRentalId, $productId, $variationId);
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->columnSpan(3)
                            ->afterStateUpdated(function ($state, $old, callable $get, callable $set) {
                                if ($state && $state !== $old) {
                                    $unit = ProductUnit::with(['product', 'variation'])->find($state);
                                    if ($unit) {
                                        // Use variation price if available, otherwise product price
                                        $dailyRate = $unit->variation?->daily_rate ?? $unit->product->daily_rate;
                                        $set('daily_rate', $dailyRate);
                                        
                                        // Auto-set days from dates
                                        $startDate = $get('../../start_date');
                                        $endDate = $get('../../end_date');
                                        
                                        if ($startDate && $endDate) {
                                            $start = Carbon::parse($startDate);
                                            $end = Carbon::parse($endDate);
                                            $days = max(1, (int) ceil($start->diffInHours($end) / 24));
                                            $set('days', $days);
                                        } else {
                                            $set('days', 1);
                                        }
                                        
                                        self::calculateLineTotal($get, $set);

                                        // Refresh status
                                        $unit->refreshStatus();
                                    }
                                }

                                if ($old) {
                                    $oldUnit = ProductUnit::find($old);
                                    if ($oldUnit) {
                                        $oldUnit->refreshStatus();
                                    }
                                }
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                        TextInput::make('daily_rate')
                            ->label('Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->columnSpan(2)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateLineTotal($get, $set)),

                        Hidden::make('days')
                            ->default(1),
                            
                        TextInput::make('discount')
                            ->label('Disc %')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateLineTotal($get, $set)),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(true)
                            ->default(0)
                            ->columnSpan(2),
                    ])
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->live()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateTotals($get, $set)),

                Actions::make([
                    Action::make('add_item')
                        ->label('Add Product')
                        ->icon('heroicon-m-plus')
                        ->button()
                        ->visible(fn ($record) => !$record || !in_array($record->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]))
                        ->form([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(\App\Models\Product::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('product_variation_id', null)),
                            
                            Select::make('product_variation_id')
                                ->label('Variation')
                                ->options(function (callable $get) {
                                    $productId = $get('product_id');
                                    if (!$productId) return [];
                                    return \App\Models\ProductVariation::where('product_id', $productId)->pluck('name', 'id');
                                })
                                ->visible(fn (callable $get) => $get('product_id') && \App\Models\Product::find($get('product_id'))?->hasVariations())
                                ->required(fn (callable $get) => $get('product_id') && \App\Models\Product::find($get('product_id'))?->hasVariations())
                                ->live(),
                        ])
                        ->action(function (array $data, callable $get, callable $set) {
                            $items = $get('items') ?? [];
                            
                            $pId = $data['product_id'];
                            $vId = $data['product_variation_id'] ?? null;
                            
                            // Construct composite ID for the repeater
                            $compositeId = $vId ? "{$pId}:{$vId}" : $pId;
                            
                            // Initialize new item with defaults
                            $newItem = [
                                'product_id' => $compositeId,
                                'product_unit_id' => null,
                                'daily_rate' => 0,
                                'days' => 1,
                                'discount' => 0,
                                'subtotal' => 0,
                            ];

                            // Calculate days from global dates
                            $startDate = $get('start_date');
                            $endDate = $get('end_date');
                            if ($startDate && $endDate) {
                                $start = Carbon::parse($startDate);
                                $end = Carbon::parse($endDate);
                                $newItem['days'] = max(1, (int) ceil($start->diffInHours($end) / 24));
                            }

                            // Try to pre-fill daily rate if possible
                            if ($vId) {
                                $variation = \App\Models\ProductVariation::find($vId);
                                if ($variation && $variation->daily_rate > 0) {
                                    $newItem['daily_rate'] = $variation->daily_rate;
                                }
                            } elseif ($pId) {
                                $product = \App\Models\Product::find($pId);
                                if ($product && $product->daily_rate > 0) {
                                    $newItem['daily_rate'] = $product->daily_rate;
                                }
                            }

                            // Calculate subtotal
                            $gross = $newItem['daily_rate'] * $newItem['days'];
                            $newItem['subtotal'] = $gross;

                            // Append with UUID key
                            $uuid = (string) \Illuminate\Support\Str::uuid();
                            $items[$uuid] = $newItem;
                            
                            $set('items', $items);
                            
                            self::calculateTotals($get, $set);
                        }),
                ]),

                // Global Calculations
                Placeholder::make('totals_section')
                    ->label('')
                    ->content(new HtmlString('<div class="border-t pt-4"></div>'))
                    ->columnSpanFull(),
                
                TextInput::make('subtotal')
                    ->label('Untaxed Amount (Subtotal)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpan(2),

                ToggleButtons::make('discount_type')
                    ->label('Discount Type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percent' => '%',
                    ])
                    ->default('fixed')
                    ->inline()
                    ->grouped()
                    ->live()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateTotals($get, $set)),

                TextInput::make('discount')
                    ->label('Discount')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->prefix(fn (callable $get) => $get('discount_type') === 'percent' ? '%' : 'Rp')
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateTotals($get, $set)),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpan(2),

                ToggleButtons::make('deposit_type')
                    ->label('Deposit Type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percent' => '%',
                    ])
                    ->default('fixed')
                    ->inline()
                    ->grouped()
                    ->live()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateTotals($get, $set)),

                TextInput::make('deposit')
                    ->label('Deposit')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->prefix(fn (callable $get) => $get('deposit_type') === 'percent' ? '%' : 'Rp')
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::calculateTotals($get, $set)),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Get available units for the given date range
     */
    public static function getAvailableUnits($startDate, $endDate, $currentRentalId = null, $productId = null, $variationId = null): array
    {
        if (!$startDate || !$endDate) {
            return ProductUnit::with(['product', 'variation'])
                ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->when($variationId, fn($q) => $q->where('product_variation_id', $variationId))
                ->get()
                ->mapWithKeys(function ($unit) {
                    $statusLabel = $unit->status !== 'available' ? " [{$unit->status}]" : '';
                    return [$unit->id => $unit->serial_number . $statusLabel];
                })
                ->toArray();
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $overlappingUnitIds = Rental::where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed')
            ->when($currentRentalId, function ($query) use ($currentRentalId) {
                $query->where('id', '!=', $currentRentalId);
            })
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<', $end)
                      ->where('end_date', '>', $start);
            })
            ->with('items')
            ->get()
            ->pluck('items')
            ->flatten()
            ->pluck('product_unit_id')
            ->unique()
            ->toArray();

        return ProductUnit::with(['product', 'variation'])
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->when($variationId, fn($q) => $q->where('product_variation_id', $variationId))
            ->get()
            ->mapWithKeys(function ($unit) use ($overlappingUnitIds) {
                $isBooked = in_array($unit->id, $overlappingUnitIds);
                if ($isBooked) return [];
                
                return [$unit->id => $unit->serial_number];
            })
            ->filter()
            ->toArray();
    }

    public static function calculateLineTotal(callable $get, callable $set): void
    {
        $dailyRate = (float) ($get('daily_rate') ?? 0);
        $days = (int) ($get('days') ?? 1);
        $discountPercent = (float) ($get('discount') ?? 0);
        
        $gross = $dailyRate * $days;
        $discountAmount = $gross * ($discountPercent / 100);
        $subtotal = max(0, $gross - $discountAmount);
        
        $set('subtotal', $subtotal);

        // Update global totals
        self::calculateTotals($get, $set);
    }

    public static function calculateDuration(callable $get, callable $set): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $days = max(1, (int) ceil($start->diffInHours($end) / 24));

            $items = $get('items') ?? [];
            foreach ($items as $key => $item) {
                $set("items.{$key}.days", $days);
                
                // Recalculate line subtotal
                $dailyRate = (float) ($item['daily_rate'] ?? 0);
                $discountPercent = (float) ($item['discount'] ?? 0);
                $gross = $dailyRate * $days;
                $discountAmount = $gross * ($discountPercent / 100);
                $subtotal = max(0, $gross - $discountAmount);
                
                $set("items.{$key}.subtotal", $subtotal);
            }
            
            // Recalculate global totals
            self::calculateTotals($get, $set);
        }
    }

    public static function calculateTotals(callable $get, callable $set): void
    {
        // Check context to determine how to access items and top-level fields
        // If we are inside repeater item, we might need ../../
        // But this method is designed to be called with a $get/$set that can access top level?
        // Or we handle both.
        
        // If called from top level (start_date, discount_type), $get('items') works.
        // If called from inside repeater, $get('items') might fail or return null.
        // Inside repeater, $get('../../items') works.
        
        $items = $get('items');
        if ($items === null) {
            $items = $get('../../items');
            $isInside = true;
        } else {
            $isInside = false;
        }
        
        $items = $items ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
        }

        // Set Subtotal
        if ($isInside) {
            $set('../../subtotal', $subtotal);
            $discountType = $get('../../discount_type') ?? 'fixed';
            $discountValue = (float) ($get('../../discount') ?? 0);
        } else {
            $set('subtotal', $subtotal);
            $discountType = $get('discount_type') ?? 'fixed';
            $discountValue = (float) ($get('discount') ?? 0);
        }

        // Calculate Discount Amount
        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discountAmount = $subtotal * ($discountValue / 100);
        } else {
            $discountAmount = $discountValue;
        }

        $total = max(0, $subtotal - $discountAmount);

        if ($isInside) {
            $set('../../total', $total);
            $depositType = $get('../../deposit_type') ?? 'fixed';
            $depositValue = (float) ($get('../../deposit') ?? 0);
        } else {
            $set('total', $total);
            $depositType = $get('deposit_type') ?? 'fixed';
            $depositValue = (float) ($get('deposit') ?? 0);
        }
        
        // Deposit Calculation (assuming it's based on subtotal if percent)
        // Or maybe based on Total? Let's use Total as it's the final amount to pay.
        // Wait, deposit is usually separate.
        // Let's use Subtotal as base for deposit if percent.
        // Actually, often deposit is % of equipment value, but here we only have rental price.
        // Let's assume % of Total Rental Price (Total).
        
        /* 
           Wait, if I update 'deposit' field value to be the calculated amount,
           I overwrite the percentage input (e.g. 10 becomes 1000).
           But I am NOT updating the 'deposit' field here.
           The 'deposit' field holds the INPUT value.
           I am NOT adding a separate 'deposit_amount' field to display.
           Wait, the user wants "Deposit" to be the amount stored in DB.
           If I use the logic "Value in Input = Stored in DB", then:
           - If Percent: Input 10 -> Store 10.
           - If Fixed: Input 1000 -> Store 1000.
           
           This means the DB stores 10 or 1000.
           And the application logic (Invoice/Payment) must know how to interpret it.
           Since I added `deposit_type` column, the backend logic CAN interpret it!
           So I DO NOT need to calculate the deposit amount in the form and store it.
           I just store the value (10 or 1000) and the type.
           
           BUT, does the `Rental` model have logic to get `deposit_amount`?
           I should add an accessor `getDepositAmountAttribute()` to the model.
           And `getDiscountAmountAttribute()`.
           
           And `total`?
           `total` is stored in DB.
           If `discount` is percentage (10), then `total` should be calculated as `subtotal - (subtotal * 10/100)`.
           The form calculates `total` and saves it.
           So `total` column is correct.
           
           But `discount` column stores 10.
           So when displaying invoice, if I just show `discount`, it shows 10.
           I need to use `discount_type` to format it.
           
           So, in `calculateTotals`:
           - We calculate `total` based on type and value.
           - We do NOT change `discount` or `deposit` field values (they are inputs).
           - We update `total` field.
           
           This works!
        */
    }
}
