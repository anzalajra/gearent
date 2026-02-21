<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalItem extends Model
{
    protected $touches = ['rental'];

    protected $fillable = [
        'rental_id',
        'product_unit_id',
        'daily_rate',
        'days',
        'subtotal',
        'discount',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            $gross = $item->daily_rate * $item->days;
            $discountAmount = $gross * ($item->discount / 100);
            $item->subtotal = max(0, $gross - $discountAmount);
        });

        static::created(function ($item) {
            $item->attachKitsFromUnit();
        });

        static::updated(function ($item) {
            if ($item->wasChanged('product_unit_id')) {
                $item->rentalItemKits()->delete();
                $item->unsetRelation('productUnit');
                $item->attachKitsFromUnit();
            }
        });

        static::saved(function ($item) {
            $item->productUnit?->refreshStatus();
        });

        static::deleted(function ($item) {
            $item->productUnit?->refreshStatus();
        });
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function rentalItemKits(): HasMany
    {
        return $this->hasMany(RentalItemKit::class);
    }

    /**
     * Attach all kits from the product unit to this rental item
     */
    public function attachKitsFromUnit(): void
    {
        $kits = $this->productUnit->kits()
            ->whereNotIn('condition', ['broken', 'lost']) // Filter out broken/lost kits
            ->get();
        
        foreach ($kits as $kit) {
            $this->rentalItemKits()->updateOrCreate(
                ['unit_kit_id' => $kit->id],
                ['condition_out' => $kit->condition]
            );
        }
    }

    /**
     * Check if all kits are returned
     */
    public function allKitsReturned(): bool
    {
        if ($this->rentalItemKits()->count() === 0) {
            return true;
        }
        return $this->rentalItemKits()->where('is_returned', false)->count() === 0;
    }

    /**
     * Get returned kits count
     */
    public function returnedKitsCount(): int
    {
        return $this->rentalItemKits()->where('is_returned', true)->count();
    }

    /**
     * Get total kits count
     */
    public function totalKitsCount(): int
    {
        return $this->rentalItemKits()->count();
    }

    /**
     * Get kits status text
     */
    public function getKitsStatusText(): string
    {
        $total = $this->totalKitsCount();
        if ($total === 0) {
            return 'No kits';
        }
        $returned = $this->returnedKitsCount();
        return "{$returned}/{$total}";
    }
}