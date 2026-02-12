<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_rental_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_rental_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage (%)',
            self::TYPE_FIXED => 'Fixed Amount (Rp)',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->start_date && $this->start_date->isFuture()) return false;
        if ($this->end_date && $this->end_date->isPast()) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        return true;
    }

    public function canBeUsedBy(Customer $customer): bool
    {
        if (!$this->isValid()) return false;
        
        if ($this->per_customer_limit) {
            $usedCount = Rental::where('user_id', $customer->id)
                ->where('discount_id', $this->id)
                ->count();
            if ($usedCount >= $this->per_customer_limit) return false;
        }
        
        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->min_rental_amount && $amount < $this->min_rental_amount) {
            return 0;
        }

        $discount = $this->type === self::TYPE_PERCENTAGE
            ? ($amount * $this->value / 100)
            : $this->value;

        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return round($discount, 2);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public static function findByCode(string $code): ?self
    {
        return self::where('code', strtoupper($code))->first();
    }
}