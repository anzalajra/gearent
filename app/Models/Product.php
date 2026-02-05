<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'daily_rate',
        'image',
        'is_active',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Check if the product is fully under maintenance
     * Returns true ONLY if all units are in maintenance/broken/lost
     * Returns false if there is at least 1 unit that is NOT maintenance (even if rented)
     */
    public function isFullyUnderMaintenance(): bool
    {
        $totalUnits = $this->units()->count();
        
        if ($totalUnits === 0) {
            return false;
        }

        $maintenanceUnits = $this->units()
            ->where(function ($query) {
                $query->where('status', ProductUnit::STATUS_MAINTENANCE)
                      ->orWhereIn('condition', ['broken', 'lost']);
            })
            ->count();

        return $totalUnits === $maintenanceUnits;
    }

    // Relasi ke Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke Brand
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Relasi ke ProductUnit
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function rentalItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(RentalItem::class, ProductUnit::class);
    }

    /**
     * Get dates where all units are booked
     */
    public function getBookedDates(): array
    {
        $bookedDates = [];
        // Consider all units that are NOT maintenance or retired as potentially available
        $unitsCount = $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->count();
        
        if ($unitsCount === 0) {
            // If no units available at all (all are retired or in maintenance), everything is booked for the next year
            $start = now();
            $end = now()->addYear();
            while ($start <= $end) {
                $bookedDates[] = $start->format('Y-m-d');
                $start->addDay();
            }
            return $bookedDates;
        }

        // Get all rentals for this product's units
        $unitIds = $this->units()->pluck('id');
        
        $rentals = RentalItem::whereIn('product_unit_id', $unitIds)
            ->whereHas('rental', function ($query) {
                $query->whereNotIn('status', [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED])
                    ->whereIn('status', [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ]);
            })
            ->with(['rental' => function ($query) {
                $query->select('id', 'start_date', 'end_date');
            }])
            ->get();

        // Calculate bookings per day
        $dailyBookings = [];
        foreach ($rentals as $item) {
            $start = $item->rental->start_date->copy()->startOfDay();
            $end = $item->rental->end_date->copy()->startOfDay();
            
            while ($start <= $end) {
                $dateStr = $start->format('Y-m-d');
                $dailyBookings[$dateStr] = ($dailyBookings[$dateStr] ?? 0) + 1;
                $start->addDay();
            }
        }

        // Dates where bookings >= available units
        foreach ($dailyBookings as $date => $count) {
            if ($count >= $unitsCount) {
                $bookedDates[] = $date;
            }
        }

        return $bookedDates;
    }

    /**
     * Find an available unit for a specific date range
     */
    public function findAvailableUnit($startDate, $endDate)
    {
        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        return $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->whereDoesntHave('rentalItems', function ($query) use ($startDate, $endDate) {
                $query->whereHas('rental', function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ])->where(function ($overlap) use ($startDate, $endDate) {
                        $overlap->where('start_date', '<', $endDate)
                               ->where('end_date', '>', $startDate);
                    });
                });
            })
            ->first();
    }
}