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
        'start_date',
        'end_date',
        'returned_date',
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
        'returned_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate rental code
        static::creating(function ($rental) {
            if (empty($rental->rental_code)) {
                $rental->rental_code = self::generateRentalCode();
            }
        });

        // Update product unit status when rental status changes
        static::updated(function ($rental) {
            if ($rental->isDirty('status')) {
                $rental->updateProductUnitStatuses();
            }
        });

        // Set product units to rented if rental is created as active
        static::created(function ($rental) {
            if ($rental->status === 'active') {
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

    // Update all product unit statuses based on rental status
    public function updateProductUnitStatuses(): void
    {
        $newStatus = match ($this->status) {
            'active' => 'rented',
            'completed', 'cancelled' => 'available',
            default => null,
        };

        if ($newStatus) {
            foreach ($this->items as $item) {
                $item->productUnit->update(['status' => $newStatus]);
            }
        }
    }

    // Mark rental as active (start rental)
    public function markAsActive(): void
    {
        $this->update(['status' => 'active']);
    }

    // Mark rental as completed (return items)
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'returned_date' => now(),
        ]);
    }

    // Mark rental as cancelled
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}