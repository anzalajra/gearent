<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'delivery_number',
        'rental_id',
        'type',
        'date',
        'checked_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public const TYPE_OUT = 'out';
    public const TYPE_IN = 'in';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (empty($delivery->delivery_number)) {
                $delivery->delivery_number = self::generateDeliveryNumber($delivery->type);
            }
        });
    }

    public static function generateDeliveryNumber(string $type): string
    {
        $prefix = $type === self::TYPE_OUT ? 'SJK' : 'SJM'; // SJ Keluar / SJ Masuk
        $date = now()->format('Ymd');
        $lastDelivery = self::where('type', $type)
            ->whereDate('created_at', today())
            ->latest()
            ->first();
        $sequence = $lastDelivery ? (int) substr($lastDelivery->delivery_number, -3) + 1 : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function allItemsChecked(): bool
    {
        return $this->items->where('is_checked', false)->count() === 0;
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_OUT => 'Keluar (Check-out)',
            self::TYPE_IN => 'Masuk (Check-in)',
        ];
    }

    public static function getTypeColor(string $type): string
    {
        return match ($type) {
            self::TYPE_OUT => 'warning',
            self::TYPE_IN => 'success',
            default => 'gray',
        };
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}