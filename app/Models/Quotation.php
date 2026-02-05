<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'number',
        'customer_id',
        'date',
        'valid_until',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUS_ON_QUOTE = 'on_quote';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ON_QUOTE => 'On Quote',
            self::STATUS_SENT => 'Sent',
            self::STATUS_ACCEPTED => 'Accepted',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($quotation) {
            if (empty($quotation->number)) {
                $quotation->number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $prefix = 'QTE';
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->latest()->first();
        $sequence = $last ? intval(substr($last->number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function recalculate(): void
    {
        $this->subtotal = $this->rentals()->sum('subtotal');
        // Assuming tax logic, for now 0 or based on settings? 
        // Rental already has 'total' which includes discount.
        // Let's sum 'total' from rentals as 'subtotal' for Quotation?
        // Or sum 'subtotal' and 'discount'?
        // Rental: subtotal - discount = total.
        // Quotation: sum(rental.total).
        
        $total = $this->rentals()->sum('total');
        $this->subtotal = $total; // Simplified for now
        $this->total = $total; // + tax if needed
        $this->save();
    }
}
