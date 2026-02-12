<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'quotation_id',
        'user_id',
        'date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUS_SENT = 'sent';
    public const STATUS_NEGOTIATION = 'negotiation';
    public const STATUS_WAITING_FOR_PAYMENT = 'waiting_for_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_SENT => 'Sent',
            self::STATUS_NEGOTIATION => 'Negotiation',
            self::STATUS_WAITING_FOR_PAYMENT => 'Waiting for Payment',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIAL => 'Partial',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($invoice) {
            if (empty($invoice->number)) {
                $invoice->number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->latest()->first();
        $sequence = $last ? intval(substr($last->number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function recalculate(): void
    {
        $total = $this->rentals()->sum('total');
        $this->subtotal = $total;
        $this->total = $total;
        $this->save();
    }
}
