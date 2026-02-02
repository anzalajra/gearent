<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'customer_id',
        'product_unit_id',
        'start_date',
        'end_date',
        'days',
        'daily_rate',
        'subtotal',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'daily_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public static function calculateDays($startDate, $endDate): int
    {
        return max(1, $startDate->diffInDays($endDate));
    }

    public function recalculate(): void
    {
        $this->days = self::calculateDays($this->start_date, $this->end_date);
        $this->subtotal = $this->daily_rate * $this->days;
        $this->save();
    }
}