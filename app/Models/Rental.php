<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    protected $fillable = [
        'rental_code',
        'customer_id',
        'start_date',
        'end_date',
        'returned_date',
        'status',
        'subtotal',
        'discount',
        'total',
        'deposit',
        'notes',
        'cancel_reason',
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

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_LATE_PICKUP = 'late_pickup';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LATE_RETURN = 'late_return';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rental) {
            if (empty($rental->rental_code)) {
                $rental->rental_code = self::generateRentalCode();
            }
        });

        static::updated(function ($rental) {
            if ($rental->isDirty('status')) {
                $rental->updateProductUnitStatuses();
            }
        });

        static::created(function ($rental) {
            if ($rental->status === self::STATUS_ACTIVE) {
                $rental->updateProductUnitStatuses();
            }
        });
    }

    public static function generateRentalCode(): string
    {
        $date = now()->format('Ymd');
        $lastRental = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastRental ? (int) substr($lastRental->rental_code, -3) + 1 : 1;
        
        return 'RNT-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    /**
     * Get the real-time status (checking for late)
     */
    public function getRealTimeStatus(): string
    {
        $now = Carbon::now();

        if ($this->status === self::STATUS_PENDING && $now->gt($this->start_date)) {
            return self::STATUS_LATE_PICKUP;
        }

        if ($this->status === self::STATUS_ACTIVE && $now->gt($this->end_date)) {
            return self::STATUS_LATE_RETURN;
        }

        return $this->status;
    }

    /**
     * Check and update late status in database
     */
    public function checkAndUpdateLateStatus(): void
    {
        $now = Carbon::now();

        if ($this->status === self::STATUS_PENDING && $now->gt($this->start_date)) {
            $this->update(['status' => self::STATUS_LATE_PICKUP]);
        }

        if ($this->status === self::STATUS_ACTIVE && $now->gt($this->end_date)) {
            $this->update(['status' => self::STATUS_LATE_RETURN]);
        }
    }

    /**
     * Update all product unit statuses based on rental status
     */
    public function updateProductUnitStatuses(): void
    {
        $newStatus = match ($this->status) {
            self::STATUS_ACTIVE, self::STATUS_LATE_RETURN => 'rented',
            self::STATUS_COMPLETED, self::STATUS_CANCELLED => 'available',
            default => null,
        };

        if ($newStatus) {
            foreach ($this->items as $item) {
                $item->productUnit->update(['status' => $newStatus]);
            }
        }
    }

    /**
     * Check if all items have conflicting rentals
     */
    public function checkAvailability(): array
    {
        $conflicts = [];

        foreach ($this->items as $item) {
            $conflictingRentals = Rental::where('id', '!=', $this->id)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_LATE_PICKUP, self::STATUS_ACTIVE, self::STATUS_LATE_RETURN])
                ->whereHas('items', function ($query) use ($item) {
                    $query->where('product_unit_id', $item->product_unit_id);
                })
                ->where(function ($query) {
                    $query->where('start_date', '<', $this->end_date)
                          ->where('end_date', '>', $this->start_date);
                })
                ->with('items.productUnit.product')
                ->get();

            if ($conflictingRentals->isNotEmpty()) {
                $conflicts[] = [
                    'item' => $item,
                    'conflicting_rentals' => $conflictingRentals,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Check if rental is available (no conflicts)
     */
    public function isAvailable(): bool
    {
        return empty($this->checkAvailability());
    }

    /**
     * Attach kits to all rental items
     */
    public function attachKitsToAllItems(): void
    {
        foreach ($this->items as $item) {
            $item->attachKitsFromUnit();
        }
    }

    /**
     * Check if all kits are returned
     */
    public function allKitsReturned(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->allKitsReturned()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate pickup - change status to active
     */
    public function validatePickup(): void
    {
        $this->attachKitsToAllItems();
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Validate return - change status to completed
     */
    public function validateReturn(): void
    {
        foreach ($this->items as $item) {
            foreach ($item->rentalItemKits as $rentalItemKit) {
                if ($rentalItemKit->condition_in && !in_array($rentalItemKit->condition_in, ['lost', 'broken'])) {
                    $rentalItemKit->unitKit->update(['condition' => $rentalItemKit->condition_in]);
                }
            }
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'returned_date' => now(),
        ]);
    }

    /**
     * Cancel rental
     */
    public function cancelRental(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Check if rental can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_LATE_PICKUP,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if rental can be deleted
     */
    public function canBeDeleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_LATE_PICKUP,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if rental can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_LATE_PICKUP,
        ]);
    }

    /**
     * Get status color
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_LATE_PICKUP => 'danger',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_LATE_RETURN => 'danger',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_LATE_PICKUP => 'Late Pickup',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_LATE_RETURN => 'Late Return',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function deliveryOut(): HasOne
    {
        return $this->hasOne(Delivery::class)->where('type', 'out');
    }

    public function deliveryIn(): HasOne
    {
        return $this->hasOne(Delivery::class)->where('type', 'in');
    }
}