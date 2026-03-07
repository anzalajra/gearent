<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'payment_gateway_id',
        'type',
        'channel_code',
        'display_name',
        'icon',
        'admin_fee',
        'admin_fee_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'type' => PaymentMethodType::class,
        'admin_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateAdminFee(float $amount): float
    {
        return match ($this->admin_fee_type) {
            'percentage' => round($amount * ($this->admin_fee / 100), 2),
            default => (float) $this->admin_fee,
        };
    }
}
