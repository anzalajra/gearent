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
        'notes',
        'purchase_date',
        'purchase_price',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    // Relasi ke Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi ke UnitKit (akan dibuat nanti)
    public function kits(): HasMany
    {
        return $this->hasMany(UnitKit::class);
    }

    // Helper: Cek apakah unit available
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}