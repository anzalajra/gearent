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
        // Only allow editing for pending and late_pickup statuses
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_LATE_PICKUP,
        ]);
    }

    /**
     * Get the real-time status of the rental
     * This checks against current time to determine if status should be updated
     */
    public function getRealTimeStatus(): string
    {
        // If already completed or cancelled, return as is
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return $this->status;
        }

        $now = now();

        // Check if pickup is late (pending but start date has passed)
        if ($this->status === self::STATUS_PENDING && $this->start_date < $now) {
            return self::STATUS_LATE_PICKUP;
        }

        // Check if return is late (active but end date has passed)
        if ($this->status === self::STATUS_ACTIVE && $this->end_date < $now) {
            return self::STATUS_LATE_RETURN;
        }

        // Return current status if no changes needed
        return $this->status;
    }

    /**
     * Check if the rental can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Only allow deleting pending rentals that haven't been picked up yet
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
}