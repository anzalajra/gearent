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
        'start_date' => 'date',
        'end_date' => 'date',
        'returned_date' => 'date',
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

        // Auto-calculate totals after save
        static::saved(function ($rental) {
            $rental->calculateTotals();
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

    public function getDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Calculate and update totals
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $total = $subtotal - $this->discount;

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }
}