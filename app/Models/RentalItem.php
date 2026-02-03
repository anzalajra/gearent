<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalItem extends Model
{
    protected $fillable = [
        'rental_id',
        'product_unit_id',
        'daily_rate',
        'days',
        'subtotal',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

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
        $kits = $this->productUnit->kits;
        
        // Skip if kits are already attached and count matches
        if ($this->rentalItemKits()->count() === $kits->count()) {
            return;
        }

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