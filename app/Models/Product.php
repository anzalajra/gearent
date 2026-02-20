<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\ProductUnit;
use App\Models\Setting;
use Carbon\Carbon;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'daily_rate',
        'buffer_time',
        'image',
        'is_active',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'buffer_time' => 'integer',
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

    // Relasi ke ProductVariation
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function hasVariations(): bool
    {
        return $this->variations()->exists();
    }

    // Relasi ke ProductComponent (Sebagai Parent)
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'parent_product_id');
    }

    // Relasi ke ProductComponent (Sebagai Child)
    public function parentComponents(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'child_product_id');
    }

    public function isBundle(): bool
    {
        return $this->components()->exists();
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
     * Get availability data for calendar
     * Returns array of date => status info
     */
    public function getAvailabilityData(): array
    {
        $data = [];
        $units = $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->whereNotIn('condition', ['broken', 'lost'])
            ->get();
        
        if ($units->isEmpty()) {
            // All booked (or rather, unavailable)
            $start = now();
            $end = now()->addYear();
            while ($start <= $end) {
                $data[$start->format('Y-m-d')] = ['status' => 'full'];
                $start->addDay();
            }
            return $data;
        }

        // Get all rentals for this product's units
        $unitIds = $units->pluck('id')->toArray();
        
        // 1. Get kits used by these units
        $kitIds = \App\Models\UnitKit::whereIn('unit_id', $unitIds)
            ->whereNotNull('linked_unit_id')
            ->pluck('linked_unit_id')
            ->unique()
            ->toArray();
            
        // 2. Get other parent units that use these kits
        $otherParentIds = \App\Models\UnitKit::whereIn('linked_unit_id', $kitIds)
            ->whereNotIn('unit_id', $unitIds)
            ->pluck('unit_id')
            ->unique()
            ->toArray();

        // 3. Get parents of My Units (if My Unit is a child/component of another unit)
        $parentOfMyUnitIds = \App\Models\UnitKit::whereIn('linked_unit_id', $unitIds)
            ->pluck('unit_id')
            ->unique()
            ->toArray();
            
        // 4. Fetch all relevant rentals
        $allRelevantUnitIds = array_unique(array_merge($unitIds, $kitIds, $otherParentIds, $parentOfMyUnitIds));
        
        $rentals = RentalItem::whereIn('product_unit_id', $allRelevantUnitIds)
            ->whereHas('rental', function ($query) {
                $query->whereNotIn('status', [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED])
                    ->whereIn('status', [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ])
                    ->where('end_date', '>=', now()->startOfDay());
            })
            ->with(['rental' => function ($query) {
                $query->select('id', 'start_date', 'end_date');
            }, 'productUnit.kits'])
            ->get();

        // 4. Map rentals to My Units
        $unitRentals = [];
        
        // Pre-compute kit usage for my units (Optimized)
        $unitKits = \App\Models\UnitKit::whereIn('unit_id', $unitIds)
            ->whereNotNull('linked_unit_id')
            ->select('unit_id', 'linked_unit_id')
            ->get();
            
        $unitKitMap = []; // UnitID -> [KitID, KitID]
        foreach ($unitKits as $uk) {
            $unitKitMap[$uk->unit_id][] = $uk->linked_unit_id;
        }
        
        // Pre-compute reverse map: KitID -> [UnitID, UnitID] (My units using this kit)
        $kitUnitMap = [];
        foreach ($unitKitMap as $uId => $kIds) {
            foreach ($kIds as $kId) {
                $kitUnitMap[$kId][] = $uId;
            }
        }

        // Pre-compute map: ParentID -> [MyUnitID] (Parents that contain My Units)
        $parentOfMyUnitMap = [];
        $myUnitParents = \App\Models\UnitKit::whereIn('linked_unit_id', $unitIds)
            ->select('unit_id', 'linked_unit_id')
            ->get();
            
        foreach ($myUnitParents as $up) {
            $parentOfMyUnitMap[$up->unit_id][] = $up->linked_unit_id;
        }

        foreach ($rentals as $item) {
            $rentedUnitId = $item->product_unit_id;
            $rental = $item->rental;
            
            // Case 1: Direct Rental of my unit
            if (in_array($rentedUnitId, $unitIds)) {
                $unitRentals[$rentedUnitId][$rental->id] = $rental;
            }
            
            // Case 2: Rental of a Kit (that my unit uses)
            if (isset($kitUnitMap[$rentedUnitId])) {
                foreach ($kitUnitMap[$rentedUnitId] as $affectedUnitId) {
                    $unitRentals[$affectedUnitId][$rental->id] = $rental;
                }
            }
            
            // Case 3: Rental of a Parent (that contains my unit)
            if (isset($parentOfMyUnitMap[$rentedUnitId])) {
                foreach ($parentOfMyUnitMap[$rentedUnitId] as $affectedUnitId) {
                    $unitRentals[$affectedUnitId][$rental->id] = $rental;
                }
            }
            
            // Case 4: Rental of another Parent (that uses a kit my unit uses)
            if ($item->productUnit && $item->productUnit->kits->isNotEmpty()) {
                foreach ($item->productUnit->kits as $kit) {
                    $kId = $kit->linked_unit_id;
                    if ($kId && isset($kitUnitMap[$kId])) {
                        foreach ($kitUnitMap[$kId] as $affectedUnitId) {
                            // If the rented unit is NOT the affected unit (avoid double counting direct rentals)
                            if ($rentedUnitId != $affectedUnitId) {
                                $unitRentals[$affectedUnitId][$rental->id] = $rental;
                            }
                        }
                    }
                }
            }
        }

        $start = now()->startOfDay();
        $end = now()->addMonths(12)->endOfDay();
        
        $globalBuffer = (int) Setting::get('rental_buffer_time', 0);
        $productBuffer = $this->buffer_time ?? 0;
        $buffer = max($globalBuffer, $productBuffer);

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $dayStart = $current->copy();
            $dayEnd = $current->copy()->endOfDay();
            
            $unitAvailabilities = []; // 'full', 'all_day', or time string 'HH:mm'

            foreach ($units as $unit) {
                $rentalsForUnit = $unitRentals[$unit->id] ?? [];
                $blockedUntil = null;
                
                foreach ($rentalsForUnit as $rental) {
                    $rentalEnd = $rental->end_date->copy()->addHours($buffer);
                    
                    // Check overlap
                    if ($rental->start_date < $dayEnd && $rentalEnd > $dayStart) {
                        if ($rental->start_date <= $dayStart && $rentalEnd >= $dayEnd) {
                            $blockedUntil = '24:00'; // Fully blocked
                            break; 
                        }
                        
                        // If rental blocks the start of the day (ends today)
                        if ($rental->start_date <= $dayStart && $rentalEnd > $dayStart) {
                            // Available after this rental
                            $endHour = $rentalEnd->format('H:i');
                            $blockedUntil = $endHour;
                        }
                        
                        // If rental starts during the day
                        if ($rental->start_date > $dayStart) {
                            // Since we can't represent "Available until X" in the current UI structure,
                            // and any rental on the day prevents a full day rental, we block the whole day.
                            $blockedUntil = '24:00';
                            break;
                        }
                    }
                }
                
                if ($blockedUntil === '24:00') {
                    $unitAvailabilities[] = 'full';
                } elseif ($blockedUntil) {
                    $unitAvailabilities[] = $blockedUntil;
                } else {
                    $unitAvailabilities[] = 'all_day';
                }
            }
            
            // Summarize day
            if (in_array('all_day', $unitAvailabilities)) {
                // At least one unit is free all day
                // Do nothing (available)
            } else {
                // No unit is free all day.
                $partials = array_filter($unitAvailabilities, fn($x) => $x !== 'full');
                
                if (empty($partials)) {
                    $data[$dateStr] = ['status' => 'full'];
                } else {
                    // Find earliest available time
                    // Convert times to minutes for comparison if needed, or string sort works for HH:mm
                    sort($partials);
                    $data[$dateStr] = ['status' => 'partial', 'start_time' => $partials[0]];
                }
            }

            $current->addDay();
        }

        return $data;
    }

    /**
     * Get dates where all units are booked
     * @deprecated Use getAvailabilityData instead
     */
    public function getBookedDates(): array
    {
        $data = $this->getAvailabilityData();
        return array_keys(array_filter($data, fn($item) => $item['status'] === 'full'));
    }

    /**
     * Find available units for a specific date range
     * Returns a collection of available ProductUnits
     */
    public function findAvailableUnits($startDate, $endDate)
    {
        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);
        
        $globalBuffer = (int) Setting::get('rental_buffer_time', 0);
        $productBuffer = $this->buffer_time ?? 0;
        $buffer = max($globalBuffer, $productBuffer);

        $availableUnits = $this->units()
            ->whereNotIn('status', [ProductUnit::STATUS_MAINTENANCE, ProductUnit::STATUS_RETIRED])
            ->whereDoesntHave('rentalItems', function ($query) use ($startDate, $endDate, $buffer) {
                $query->whereHas('rental', function ($q) use ($startDate, $endDate, $buffer) {
                    $q->whereIn('status', [
                        Rental::STATUS_PENDING,
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN
                    ])->where(function ($overlap) use ($startDate, $endDate, $buffer) {
                        $overlap->where('start_date', '<', $endDate)
                                ->whereRaw("DATE_ADD(end_date, INTERVAL ? HOUR) > ?", [$buffer, $startDate]);
                    });
                });
            })
            // Check if this unit is a BUNDLE that has a component that is rented
            ->whereDoesntHave('kits', function ($qKit) use ($startDate, $endDate, $buffer) {
                $qKit->whereNotNull('linked_unit_id')
                     ->whereHas('linkedUnit', function ($qLinkedUnit) use ($startDate, $endDate, $buffer) {
                        // Check if the component is unavailable either directly or via another parent
                        $qLinkedUnit->where(function ($query) use ($startDate, $endDate, $buffer) {
                            // 1. Component is directly rented
                            $query->whereHas('rentalItems', function ($qRentalItem) use ($startDate, $endDate, $buffer) {
                                $qRentalItem->whereHas('rental', function ($qRental) use ($startDate, $endDate, $buffer) {
                                    $qRental->whereIn('status', [
                                        Rental::STATUS_PENDING,
                                        Rental::STATUS_CONFIRMED,
                                        Rental::STATUS_ACTIVE,
                                        Rental::STATUS_LATE_PICKUP,
                                        Rental::STATUS_LATE_RETURN
                                    ])->where(function ($overlap) use ($startDate, $endDate, $buffer) {
                                        $overlap->where('start_date', '<', $endDate)
                                                ->whereRaw("DATE_ADD(end_date, INTERVAL ? HOUR) > ?", [$buffer, $startDate]);
                                    });
                                });
                            })
                            // 2. Component is part of ANOTHER rented bundle (Parent is rented)
                            ->orWhereHas('linkedInKits', function ($qLink) use ($startDate, $endDate, $buffer) {
                                 $qLink->whereHas('unit', function ($qParentUnit) use ($startDate, $endDate, $buffer) {
                                      $qParentUnit->whereHas('rentalItems', function ($qRentalItem) use ($startDate, $endDate, $buffer) {
                                            $qRentalItem->whereHas('rental', function ($qRental) use ($startDate, $endDate, $buffer) {
                                                $qRental->whereIn('status', [
                                                    Rental::STATUS_PENDING,
                                                    Rental::STATUS_CONFIRMED,
                                                    Rental::STATUS_ACTIVE,
                                                    Rental::STATUS_LATE_PICKUP,
                                                    Rental::STATUS_LATE_RETURN
                                                ])->where(function ($overlap) use ($startDate, $endDate, $buffer) {
                                                    $overlap->where('start_date', '<', $endDate)
                                                            ->whereRaw("DATE_ADD(end_date, INTERVAL ? HOUR) > ?", [$buffer, $startDate]);
                                                });
                                            });
                                      });
                                 });
                            });
                        });
                     });
            })
            // Check if this unit is a COMPONENT of a Bundle that is rented
            ->whereDoesntHave('linkedInKits', function ($qLink) use ($startDate, $endDate, $buffer) {
                 $qLink->whereHas('unit', function ($qParentUnit) use ($startDate, $endDate, $buffer) {
                      $qParentUnit->whereHas('rentalItems', function ($qRentalItem) use ($startDate, $endDate, $buffer) {
                            $qRentalItem->whereHas('rental', function ($qRental) use ($startDate, $endDate, $buffer) {
                                $qRental->whereIn('status', [
                                    Rental::STATUS_PENDING,
                                    Rental::STATUS_CONFIRMED,
                                    Rental::STATUS_ACTIVE,
                                    Rental::STATUS_LATE_PICKUP,
                                    Rental::STATUS_LATE_RETURN
                                ])->where(function ($overlap) use ($startDate, $endDate, $buffer) {
                                    $overlap->where('start_date', '<', $endDate)
                                            ->whereRaw("DATE_ADD(end_date, INTERVAL ? HOUR) > ?", [$buffer, $startDate]);
                                });
                            });
                      });
                 });
            })
            ->get();
            
        return $availableUnits;
    }


    /**
     * Find an available unit for a specific date range
     */
    public function findAvailableUnit($startDate, $endDate)
    {
        return $this->findAvailableUnits($startDate, $endDate)->first();
    }
}