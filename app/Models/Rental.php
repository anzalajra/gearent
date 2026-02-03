<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'rental_code',
        'customer_id',
        'discount_id',
        'discount_code',
        'start_date',
        'end_date',
        'status',
        'subtotal',
        'discount',
        'total',
        'deposit',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_LATE_PICKUP = 'late_pickup';
    public const STATUS_LATE_RETURN = 'late_return';

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountRelation(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
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
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_LATE_PICKUP => 'Late Pickup',
            self::STATUS_LATE_RETURN => 'Late Return',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'gray',
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
            self::STATUS_LATE_PICKUP,
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

        if ($this->status === self::STATUS_ACTIVE && $this->end_date < $now) {
            return self::STATUS_LATE_RETURN;
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

        if ($this->status === self::STATUS_PENDING && $this->start_date < $now) {
            $this->update(['status' => self::STATUS_LATE_PICKUP]);
        }

        if ($this->status === self::STATUS_ACTIVE && $this->end_date < $now) {
            $this->update(['status' => self::STATUS_LATE_RETURN]);
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
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_LATE_PICKUP, self::STATUS_LATE_RETURN])
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
     * Validate pickup and change status to active
     */
    public function validatePickup(): void
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_LATE_PICKUP])) {
            throw new \Exception('Cannot validate pickup for this rental status.');
        }

        // Check if all items with kits have their kits checked
        foreach ($this->items as $item) {
            if ($item->productUnit->kits->count() > 0) {
                $checkedKits = $item->rentalItemKits->count();
                $totalKits = $item->productUnit->kits->count();
                
                if ($checkedKits < $totalKits) {
                    throw new \Exception('All kit items must be checked before validating pickup.');
                }
            }
        }

        $this->update(['status' => self::STATUS_ACTIVE]);
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
        $this->total = $this->subtotal - $discountAmount;
        $this->deposit = $this->total * (Setting::get('deposit_percentage', 30) / 100);
        $this->save();
        $discount->incrementUsage();
    }

    public function removeDiscount(): void
    {
        $this->discount_id = null;
        $this->discount_code = null;
        $this->discount = 0;
        $this->total = $this->subtotal;
        $this->deposit = $this->total * (Setting::get('deposit_percentage', 30) / 100);
        $this->save();
    }

    public function recalculateTotal(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        if ($this->discountRelation) {
            $this->discount = $this->discountRelation->calculateDiscount($this->subtotal);
        }
        $this->total = $this->subtotal - $this->discount;
        $this->deposit = $this->total * (Setting::get('deposit_percentage', 30) / 100);
        $this->save();
    }

    public function validateReturn(): void
    {
        $allReturned = $this->items()
            ->whereHas('rentalItemKits', function ($query) {
                $query->where('is_returned', false);
            })->doesntExist();

        if ($allReturned) {
            $this->update(['status' => self::STATUS_COMPLETED]);
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

        if ($deliveryOut->status === Delivery::STATUS_DRAFT) {
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

        if ($deliveryIn->status === Delivery::STATUS_DRAFT) {
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
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_LATE_PICKUP])) {
            throw new \Exception('Cannot cancel this rental. Only pending or late pickup rentals can be cancelled.');
        }

        // Release all product units back to available
        foreach ($this->items as $item) {
            if ($item->productUnit && $item->productUnit->status === ProductUnit::STATUS_RENTED) {
                $item->productUnit->update(['status' => ProductUnit::STATUS_AVAILABLE]);
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
    }

    /**
     * Check if the rental can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_LATE_PICKUP,
        ]);
    }
}