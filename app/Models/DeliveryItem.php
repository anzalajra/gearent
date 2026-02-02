<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItem extends Model
{
    protected $fillable = [
        'delivery_id',
        'rental_item_id',
        'rental_item_kit_id',
        'is_checked',
        'condition',
        'notes',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function rentalItem(): BelongsTo
    {
        return $this->belongsTo(RentalItem::class);
    }

    public function rentalItemKit(): BelongsTo
    {
        return $this->belongsTo(RentalItemKit::class);
    }

    public static function getConditionOptions(): array
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
        ];
    }

    public static function getConditionInOptions(): array
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'lost' => 'Lost',
            'broken' => 'Broken',
        ];
    }

    public static function getConditionColor(string $condition): string
    {
        return match ($condition) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'danger',
            'lost' => 'danger',
            'broken' => 'danger',
            default => 'gray',
        };
    }
}