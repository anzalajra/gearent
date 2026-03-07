<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaasInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasInvoice extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'tenant_subscription_id',
        'invoice_number',
        'amount',
        'tax',
        'total',
        'currency',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'payment_method',
        'payment_reference',
        'payment_gateway_id',
        'payment_method_id',
        'payment_data',
        'gateway_reference_id',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => SaasInvoiceStatus::class,
        'payment_data' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantSubscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function isPaid(): bool
    {
        return $this->status === SaasInvoiceStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === SaasInvoiceStatus::Pending;
    }

    public function hasPaymentInstructions(): bool
    {
        return ! empty($this->payment_data);
    }

    public function isPaymentExpired(): bool
    {
        if (! $this->payment_data || ! isset($this->payment_data['expires_at'])) {
            return false;
        }

        return now()->isAfter($this->payment_data['expires_at']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', SaasInvoiceStatus::Overdue)
            ->orWhere(fn ($q) => $q->where('status', SaasInvoiceStatus::Pending)->where('due_at', '<', now()));
    }

    public function scopePending($query)
    {
        return $query->where('status', SaasInvoiceStatus::Pending);
    }

    public function scopePaid($query)
    {
        return $query->where('status', SaasInvoiceStatus::Paid);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [SaasInvoiceStatus::Pending, SaasInvoiceStatus::Overdue]);
    }

    public function isDueSoon(int $days = 7): bool
    {
        return $this->status === SaasInvoiceStatus::Pending
            && $this->due_at !== null
            && $this->due_at->isFuture()
            && $this->due_at->diffInDays(now()) <= $days;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $count = self::where('tenant_id', $invoice->tenant_id)->count() + 1;
                $invoice->invoice_number = 'ZWL-'.now()->format('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
