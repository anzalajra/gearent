<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'serial_number',
        'condition',
        'status',
        'purchase_date',
        'purchase_price',
        'notes',
        'last_checked_at',
        'maintenance_status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'last_checked_at' => 'datetime',
    ];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RENTED = 'rented';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_RETIRED = 'retired';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function kits(): HasMany
    {
        return $this->hasMany(UnitKit::class, 'unit_id');
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_RENTED => 'Rented',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_RETIRED => 'Retired',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_SCHEDULED => 'primary',
            self::STATUS_RENTED => 'warning',
            self::STATUS_MAINTENANCE => 'info',
            self::STATUS_RETIRED => 'danger',
            default => 'gray',
        };
    }

    public static function getConditionOptions(): array
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'broken' => 'Broken',
            'lost' => 'Lost',
        ];
    }

    /**
     * Refresh unit status based on rentals and conditions
     */
    public function refreshStatus(): void
    {
        // If status is RETIRED, don't auto-change it
        if ($this->status === self::STATUS_RETIRED) {
            return;
        }

        $newStatus = self::STATUS_AVAILABLE;

        // Check for active rentals (Rented)
        $isRented = $this->rentalItems()
            ->whereHas('rental', function ($query) {
                $query->whereIn('status', [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN]);
            })->exists();

        if ($isRented) {
            $newStatus = self::STATUS_RENTED;
        } else {
            // If status is MAINTENANCE, we only change it if it's rented (handled above)
            // Otherwise we keep it as MAINTENANCE until manually changed
            if ($this->status === self::STATUS_MAINTENANCE) {
                return;
            }

            // Check for scheduled rentals
            $isScheduled = $this->rentalItems()
                ->whereHas('rental', function ($query) {
                    $query->whereIn('status', [Rental::STATUS_PENDING, Rental::STATUS_LATE_PICKUP]);
                })->exists();

            if ($isScheduled) {
                $newStatus = self::STATUS_SCHEDULED;
            }
        }

        // Only update if status changed to avoid loops/unnecessary queries
        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }
}