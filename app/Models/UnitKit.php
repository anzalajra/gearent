<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitKit extends Model
{
    protected $fillable = [
        'unit_id',
        'name',
        'serial_number',
        'condition',
        'notes',
    ];

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function getConditionColor(): string
    {
        return match ($this->condition) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'danger',
            default => 'gray',
        };
    }
}