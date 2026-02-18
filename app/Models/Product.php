<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Setting;

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
        'is_taxable',
        'price_includes_tax',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'price_includes_tax' => 'boolean',
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

    // Relasi ke ProductVariation
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function hasVariations(): bool
    {
        return $this->variations()->exists();
    }

    public function rentalItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(RentalItem::class, ProductUnit::class);
    }

    public function excludedCustomerCategories(): BelongsToMany
    {
        return $this->belongsToMany(CustomerCategory::class, 'product_visibility_exclusions', 'product_id', 'customer_category_id');
    }

    /**
     * Scope to filter products visible for a specific customer
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null $customer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleForCustomer(Builder $query, $customer): Builder
    {
        if (!$customer || !$customer->customer_category_id) {
            return $query;
        }

        return $query->whereDoesntHave('excludedCustomerCategories', function ($q) use ($customer) {
            $q->where('customer_categories.id', $customer->customer_category_id);
        });
    }

    /**
     * Check if product is visible for specific customer instance
     * 
     * @param \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null $customer
     * @return bool
     */
    public function isVisibleForCustomer($customer): bool
    {
        if (!$customer || !$customer->customer_category_id) {
            return true;
        }

        return !$this->excludedCustomerCategories()
            ->where('customer_categories.id', $customer->customer_category_id)
            ->exists();
    }

    /**
     * Get availability calendar data
     * Returns ['booked' => [], 'partial' => []]
     */
    public function getAvailabilityCalendar(): array
    {
        $bookedDates = [];
        $partialDates = [];
        
        $unitsCount = $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->count();
        
        if ($unitsCount === 0) {
            $start = now();
            $end = now()->addYear();
            while ($start <= $end) {
                $bookedDates[] = $start->format('Y-m-d');
                $start->addDay();
            }
            return ['booked' => $bookedDates, 'partial' => []];
        }

        $unitIds = $this->units()->pluck('id');
        $bufferHours = (int) Setting::get('rental_buffer_time', 0);
        
        $rentals = RentalItem::whereIn('product_unit_id', $unitIds)
            ->whereHas('rental', function ($query) {
                $query->whereNotIn('status', [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED])
                    ->whereIn('status', [
                        Rental::STATUS_QUOTATION,
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ]);
            })
            ->with(['rental' => function ($query) {
                $query->select('id', 'start_date', 'end_date');
            }])
            ->get();

        $dailyStats = []; // 'Y-m-d' => ['full' => 0, 'partial' => 0]
        
        foreach ($rentals as $item) {
            $rentalStart = $item->rental->start_date;
            $rentalEnd = $item->rental->end_date->copy()->addHours($bufferHours);
            
            $periodStart = $rentalStart->copy()->startOfDay();
            $periodEnd = $rentalEnd->copy()->startOfDay();
            
            $current = $periodStart->copy();
            
            while ($current <= $periodEnd) {
                $dateStr = $current->format('Y-m-d');
                $dayStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();

                // Skip if rental ends exactly at start of day (0 duration on this day)
                if ($rentalEnd->eq($dayStart)) {
                    $current->addDay();
                    continue;
                }
                
                $isFullDay = ($rentalStart->lte($dayStart) && $rentalEnd->gte($dayEnd));
                
                if (!isset($dailyStats[$dateStr])) {
                    $dailyStats[$dateStr] = ['full' => 0, 'partial' => 0];
                }
                
                if ($isFullDay) {
                    $dailyStats[$dateStr]['full']++;
                } else {
                    $dailyStats[$dateStr]['partial']++;
                }
                
                $current->addDay();
            }
        }

        foreach ($dailyStats as $date => $stats) {
            $totalOccupancy = $stats['full'] + $stats['partial'];
            
            if ($stats['full'] >= $unitsCount) {
                $bookedDates[] = $date;
            } elseif ($totalOccupancy >= $unitsCount) {
                $partialDates[] = $date;
            }
        }
        
        return ['booked' => $bookedDates, 'partial' => $partialDates];
    }

    /**
     * Find available units for a specific date range
     * Returns a collection of available ProductUnits
     */
    public function findAvailableUnits($startDate, $endDate)
    {
        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        return $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->whereDoesntHave('rentalItems', function ($query) use ($startDate, $endDate) {
                $query->whereHas('rental', function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [
                        Rental::STATUS_QUOTATION,
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ])->where(function ($overlap) use ($startDate, $endDate) {
                        $overlap->where('start_date', '<', $endDate)
                                ->where('end_date', '>', $startDate);
                    });
                });
            })
            ->get();
    }

    /**
     * Find an available unit for a specific date range
     */
    public function findAvailableUnit($startDate, $endDate)
    {
        return $this->findAvailableUnits($startDate, $endDate)->first();
    }
}
