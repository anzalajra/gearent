<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItemKit extends Model
{
    protected $fillable = [
        'rental_item_id',
        'unit_kit_id',
        'condition_out',
        'condition_in',
        'is_returned',
        'notes',
    ];

    protected $casts = [
        'is_returned' => 'boolean',
    ];

    public function rentalItem(): BelongsTo
    {
        return $this->belongsTo(RentalItem::class);
    }

    public function unitKit(): BelongsTo
    {
        return $this->belongsTo(UnitKit::class);
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
}