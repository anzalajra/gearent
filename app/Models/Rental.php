<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'rental_code',
        'user_id',
        'discount_id',
        'quotation_id',
        'invoice_id',
        'discount_code',
        'start_date',
        'end_date',
        'returned_date',
        'status',
        'subtotal',
        'discount',
        'discount_type',
        'total',
        'deposit',
        'deposit_type',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'returned_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_LATE_PICKUP = 'late_pickup';
    public const STATUS_LATE_RETURN = 'late_return';
    public const STATUS_PARTIAL_RETURN = 'partial_return';

    protected static function booted()
    {
        static::created(function ($rental) {
            // Admin Notification
            $admins = \App\Models\User::all();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewBookingNotification($rental));

            // Customer Notification
            if ($rental->customer) {
                $rental->customer->notify(new \App\Notifications\BookingConfirmedNotification($rental));
            }
        });

        static::saved(function ($rental) {
            $rental->refreshUnitStatuses();
        });

        static::deleting(function ($rental) {
            $units = $rental->items->map(fn($item) => $item->productUnit)->filter();
            
            static::deleted(function () use ($units) {
                foreach ($units as $unit) {
                    $unit->refreshStatus();
                }
            });
        });
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($rental) {
            if (empty($rental->rental_code)) {
                $rental->rental_code = self::generateRentalCode();
            }
        });
    }

    public static function generateRentalCode(): string
    {
        $prefix = 'RNT';
        $date = now()->format('Ymd');
        $lastRental = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastRental ? intval(substr($lastRental->rental_code, -4)) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @deprecated Use user() instead
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function discountRelation(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_LATE_PICKUP => 'Late Pickup',
            self::STATUS_LATE_RETURN => 'Late Return',
            self::STATUS_PARTIAL_RETURN => 'Partial Return',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'purple',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_PARTIAL_RETURN => 'orange',
            self::STATUS_LATE_PICKUP, self::STATUS_LATE_RETURN => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if the rental can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_LATE_PICKUP,
            self::STATUS_ACTIVE,
            self::STATUS_LATE_RETURN,
            self::STATUS_PARTIAL_RETURN,
        ]);
    }

    /**
     * Get the real-time status of the rental
     */
    public function getRealTimeStatus(): string
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return $this->status;
        }

        $now = now();

        if ($this->status === self::STATUS_PENDING && $this->start_date < $now) {
            return self::STATUS_LATE_PICKUP;
        }

        // Check for Partial Return condition (Dynamic)
        $hasPartialReturn = $this->deliveries->where('type', Delivery::TYPE_IN)->count() > 1;

        if ($this->end_date < $now) {
            if ($this->status === self::STATUS_ACTIVE || $this->status === self::STATUS_PARTIAL_RETURN || ($this->status === self::STATUS_ACTIVE && $hasPartialReturn)) {
                return self::STATUS_LATE_RETURN;
            }
        }

        if ($this->status === self::STATUS_ACTIVE && $hasPartialReturn) {
            return self::STATUS_PARTIAL_RETURN;
        }

        return $this->status;
    }

    /**
     * Check and update late status in database
     */
    public function checkAndUpdateLateStatus(): void
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return;
        }

        $now = now();
        $newStatus = $this->status;

        if ($this->status === self::STATUS_PENDING && $this->start_date < $now) {
            $newStatus = self::STATUS_LATE_PICKUP;
        }

        if (($this->status === self::STATUS_ACTIVE || $this->status === self::STATUS_PARTIAL_RETURN) && $this->end_date < $now) {
            $newStatus = self::STATUS_LATE_RETURN;
        }

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
            $this->refreshUnitStatuses();
        }
    }

    /**
     * Refresh all product unit statuses associated with this rental
     */
    public function refreshUnitStatuses(): void
    {
        foreach ($this->items as $item) {
            if ($item->productUnit) {
                $item->productUnit->refreshStatus();
            }
        }
    }

    /**
     * Check availability of rental items (conflicts with other active rentals)
     */
    public function checkAvailability(): array
    {
        $conflicts = [];

        foreach ($this->items as $item) {
            // Check if the product unit is already rented in an overlapping period
            $conflictingRentals = self::where('id', '!=', $this->id)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_ACTIVE, self::STATUS_LATE_PICKUP, self::STATUS_LATE_RETURN])
                ->where(function ($query) {
                    $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                        ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                        ->orWhere(function ($q) {
                            $q->where('start_date', '<=', $this->start_date)
                              ->where('end_date', '>=', $this->end_date);
                        });
                })
                ->whereHas('items', function ($query) use ($item) {
                    $query->where('product_unit_id', $item->product_unit_id);
                })
                ->with('customer')
                ->get();

            if ($conflictingRentals->isNotEmpty()) {
                $conflicts[] = [
                    'product_unit' => $item->productUnit,
                    'conflicting_rentals' => $conflictingRentals,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Resolve conflicts by removing conflicting items from other rentals
     */
    public function resolveConflicts(array $conflicts): void
    {
        foreach ($conflicts as $conflict) {
            $unit = $conflict['product_unit'];
            $conflictingRentals = $conflict['conflicting_rentals'];

            foreach ($conflictingRentals as $rental) {
                // Find the conflicting item
                $item = $rental->items()
                    ->where('product_unit_id', $unit->id)
                    ->first();

                if ($item) {
                    $item->delete();
                    
                    // Recalculate totals for the other rental
                    $rental->refresh();
                    $subtotal = $rental->items->sum('subtotal');
                    
                    // Update subtotal
                    $rental->subtotal = $subtotal;
                    
                    // Recalculate total (keeping existing discount amount for fixed, or logic for percent)
                    // Since applyDiscount logic is complex, we'll do a simple update here
                    // assuming the admin will review the other rental if needed.
                    $rental->total = max(0, $subtotal - $rental->discount);
                    
                    $rental->save();
                }
            }
        }
    }

    /**
     * Validate pickup and change status to active
     */
    public function validatePickup(): void
    {
        if (! in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_LATE_PICKUP])) {
            throw new \Exception('Cannot validate pickup for this rental status.');
        }

        // Check availability of rental items (conflicts with other active rentals)
        $conflicts = $this->checkAvailability();
        if (! empty($conflicts)) {
            $messages = [];
            foreach ($conflicts as $conflict) {
                $unitName = $conflict['product_unit']->product->name;
                $serial = $conflict['product_unit']->serial_number;
                
                $rentalInfo = $conflict['conflicting_rentals']->map(function ($r) {
                    $customerName = $r->customer->name ?? 'Unknown';
                    return "{$r->rental_code} ($customerName)";
                })->implode(', ');

                $messages[] = "$unitName ($serial) vs $rentalInfo";
            }
            $unitList = implode('; ', $messages);
            throw new \Exception("Cannot validate pickup. The following units have scheduling conflicts: $unitList. Please swap them.");
        }

        // Check if any unit is physically unavailable (e.g. still rented/late return from another customer)
        foreach ($this->items as $item) {
            if ($item->productUnit) {
                // Refresh status first to be sure
                $item->productUnit->refreshStatus();

                if (in_array($item->productUnit->status, [ProductUnit::STATUS_RENTED, ProductUnit::STATUS_MAINTENANCE])) {
                    throw new \Exception("Unit {$item->productUnit->serial_number} ({$item->productUnit->product->name}) is currently {$item->productUnit->status}. Please swap the unit in the list before validating pickup.");
                }
            }
        }

        // Check if all items with kits have their kits checked
        foreach ($this->items as $item) {
            if ($item->productUnit->kits->count() > 0) {
                $checkedKits = $item->rentalItemKits->count();
                // Filter out broken/lost kits to match attachKitsFromUnit logic
                $totalKits = $item->productUnit->kits->whereNotIn('condition', ['broken', 'lost'])->count();
                
                if ($checkedKits < $totalKits) {
                    throw new \Exception('All kit items must be checked before validating pickup.');
                }
            }
        }

        $this->update(['status' => self::STATUS_ACTIVE]);

        // Update product unit statuses to Rented
        foreach ($this->items as $item) {
            if ($item->productUnit) {
                $item->productUnit->refreshStatus();
            }
        }
    }

    /**
     * Check if the rental can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function applyDiscount(Discount $discount): void
    {
        $discountAmount = $discount->calculateDiscount($this->subtotal);
        $this->discount_id = $discount->id;
        $this->discount_code = $discount->code;
        $this->discount = $discountAmount;
        $this->discount_type = 'fixed';
        $this->total = $this->subtotal - $discountAmount;
        // Deposit logic: if percent, it auto-adjusts via accessor. If fixed, it stays.
        // We do NOT overwrite deposit here to respect manual overrides.
        $this->save();
        $discount->incrementUsage();
    }

    public function removeDiscount(): void
    {
        $this->discount_id = null;
        $this->discount_code = null;
        $this->discount = 0;
        $this->discount_type = 'fixed';
        $this->total = $this->subtotal;
        // We do NOT overwrite deposit here to respect manual overrides.
        $this->save();
    }

    public function recalculateTotal(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        
        $discountAmount = 0;
        if ($this->discountRelation) {
            $discountAmount = $this->discountRelation->calculateDiscount($this->subtotal);
        } else {
            if ($this->discount_type === 'percent') {
                $discountAmount = $this->subtotal * ($this->discount / 100);
            } else {
                $discountAmount = $this->discount;
            }
        }
        
        $this->total = max(0, $this->subtotal - $discountAmount);
        // Deposit is not overwritten automatically anymore.
        $this->save();
    }

    public function getDiscountAmountAttribute(): float
    {
        if ($this->discount_type === 'percent') {
            return $this->subtotal * ($this->discount / 100);
        }
        return (float) $this->discount;
    }

    public function getDepositAmountAttribute(): float
    {
        if ($this->deposit_type === 'percent') {
            return $this->total * ($this->deposit / 100);
        }
        return (float) $this->deposit;
    }

    public static function calculateDeposit(float $amount): float
    {
        // Check if deposit is enabled
        $enabled = Setting::get('deposit_enabled', true);
        if (!$enabled) {
            return 0;
        }

        $type = Setting::get('deposit_type', 'percentage');
        
        // Determine default amount based on old setting if available
        $defaultAmount = 30;
        if ($type === 'percentage') {
             $oldValue = Setting::get('deposit_percentage');
             if ($oldValue !== null) {
                 $defaultAmount = $oldValue;
             }
        }
        
        $settingAmount = Setting::get('deposit_amount', $defaultAmount);

        if ($type === 'percentage') {
            return $amount * ($settingAmount / 100);
        }

        return $settingAmount;
    }

    public static function calculateLateFee(float $dailyRate, int $daysLate): float
    {
         $type = Setting::get('late_fee_type', 'percentage');
         
         $defaultAmount = 10;
         if ($type === 'percentage') {
             $oldValue = Setting::get('late_fee_percentage');
             if ($oldValue !== null) {
                 $defaultAmount = $oldValue;
             }
         }
         
         $amount = Setting::get('late_fee_amount', $defaultAmount);
         
         if ($type === 'percentage') {
             return ($dailyRate * ($amount / 100)) * $daysLate;
         }
         
         return $amount * $daysLate;
    }


    public function validateReturn(): void
    {
        // Check if there are any pending/draft IN deliveries
        // If there are pending deliveries, it means not all items are returned/checked yet.
        $hasPendingDeliveries = $this->deliveries()
            ->where('type', Delivery::TYPE_IN)
            ->whereIn('status', [Delivery::STATUS_DRAFT, Delivery::STATUS_PENDING])
            ->exists();
            
        if ($hasPendingDeliveries) {
            return;
        }

        // Ensure at least one IN delivery exists
        $hasInDelivery = $this->deliveries()
            ->where('type', Delivery::TYPE_IN)
            ->exists();
            
        if (!$hasInDelivery) {
            return;
        }

        $this->update(['status' => self::STATUS_COMPLETED]);

        // Update product unit statuses based on return condition
        // We iterate all IN deliveries to find the condition for each item
        $inDeliveries = $this->deliveries()->where('type', Delivery::TYPE_IN)->with('items')->get();
        
        foreach ($this->items as $item) {
            if ($item->productUnit) {
                // Find the delivery item for this rental item in ANY IN delivery
                $condition = null;
                
                foreach ($inDeliveries as $delivery) {
                    $dItem = $delivery->items
                        ->where('rental_item_id', $item->id)
                        ->whereNull('rental_item_kit_id')
                        ->first();

                    if ($dItem && $dItem->condition) {
                        $condition = $dItem->condition;
                    }
                }

                if ($condition && in_array($condition, ['broken', 'lost'])) {
                    $item->productUnit->update(['status' => ProductUnit::STATUS_MAINTENANCE]);
                } else {
                    $item->productUnit->refreshStatus();
                }
            }
        }
    }

    /**
     * Create delivery documents (Out and In) for this rental
     */
    public function createDeliveries(): void
    {
        // Ensure all rental items have their kits attached first
        foreach ($this->items as $item) {
            $item->attachKitsFromUnit();
        }
        $this->load('items.rentalItemKits');

        // Create or Update Delivery Out (SJK)
        $deliveryOut = $this->deliveries()->where('type', Delivery::TYPE_OUT)->first();
        if (!$deliveryOut) {
            $deliveryOut = Delivery::create([
                'rental_id' => $this->id,
                'type' => Delivery::TYPE_OUT,
                'date' => $this->start_date,
                'status' => Delivery::STATUS_DRAFT,
            ]);
        }

        if ($deliveryOut->status === Delivery::STATUS_DRAFT || $deliveryOut->items()->count() === 0) {
            foreach ($this->items as $item) {
                // Main Unit
                $deliveryOut->items()->firstOrCreate([
                    'rental_item_id' => $item->id,
                    'rental_item_kit_id' => null,
                ], [
                    'is_checked' => false,
                    'condition' => $item->productUnit->condition,
                ]);

                // Kits
                foreach ($item->rentalItemKits as $kit) {
                    $deliveryOut->items()->firstOrCreate([
                        'rental_item_id' => $item->id,
                        'rental_item_kit_id' => $kit->id,
                    ], [
                        'is_checked' => false,
                        'condition' => $kit->condition_out,
                    ]);
                }
            }
        }

        // Create or Update Delivery In (SJM)
        $deliveryIn = $this->deliveries()->where('type', Delivery::TYPE_IN)->first();
        if (!$deliveryIn) {
            $deliveryIn = Delivery::create([
                'rental_id' => $this->id,
                'type' => Delivery::TYPE_IN,
                'date' => $this->end_date,
                'status' => Delivery::STATUS_DRAFT,
            ]);
        }

        if ($deliveryIn->status === Delivery::STATUS_DRAFT || $deliveryIn->items()->count() === 0) {
            foreach ($this->items as $item) {
                // Main Unit
                $deliveryIn->items()->firstOrCreate([
                    'rental_item_id' => $item->id,
                    'rental_item_kit_id' => null,
                ], [
                    'is_checked' => false,
                ]);

                // Kits
                foreach ($item->rentalItemKits as $kit) {
                    $deliveryIn->items()->firstOrCreate([
                        'rental_item_id' => $item->id,
                        'rental_item_kit_id' => $kit->id,
                    ], [
                        'is_checked' => false,
                    ]);
                }
            }
        }
    }

    /**
     * Cancel the rental with a reason
     * 
     * @param string $reason The reason for cancellation
     * @throws \Exception If rental cannot be cancelled
     */
    public function cancelRental(string $reason): void
    {
        // Validate that rental can be cancelled
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_LATE_PICKUP])) {
            throw new \Exception('Cannot cancel this rental. Only pending, confirmed or late pickup rentals can be cancelled.');
        }

        // Release all product units back to available/scheduled
        foreach ($this->items as $item) {
            if ($item->productUnit) {
                $item->productUnit->refreshStatus();
            }
        }

        // Decrement discount usage if a discount was applied
        if ($this->discountRelation) {
            $this->discountRelation->decrement('usage_count');
        }

        // Update rental status and save cancel reason
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ]);

        // Cancel all associated deliveries
        $this->deliveries()->update(['status' => Delivery::STATUS_CANCELLED]);
    }

    /**
     * Check if the rental can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_LATE_PICKUP,
        ]);
    }
}