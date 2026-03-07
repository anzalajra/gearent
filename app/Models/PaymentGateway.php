<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'code',
        'credentials',
        'is_active',
        'is_sandbox',
        'notes',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
