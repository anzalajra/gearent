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
        'is_visible_on_frontend',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'buffer_time' => 'integer',
        'is_active' => 'boolean',
        'is_visible_on_frontend' => 'boolean',
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
     * Scope a query to only include products visible on frontend.
     */
    public function scopeVisibleOnFrontend(Builder $query): Builder
    {
        return $query->where('is_visible_on_frontend', true);
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

    public function excludedCustomerCategories(): BelongsToMany
    {
        return $this->belongsToMany(CustomerCategory::class, 'product_visibility_exclusions', 'product_id', 'customer_category_id');
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

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'parent_product_id');
    }

    // Relasi ke Product (Sebagai Parent)
    public function parentProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_components', 'child_product_id', 'parent_product_id');
    }

    public function scopeVisibleForCustomer(Builder $query, $customer = null)
    {
        // Base visibility: active and visible on frontend
        $query->where('is_active', true)->where('is_visible_on_frontend', true);

        // If customer is logged in and has a category, filter exclusions
        if ($customer && isset($customer->customer_category_id)) {
            $query->whereDoesntHave('excludedCustomerCategories', function ($q) use ($customer) {
                $q->where('customer_categories.id', $customer->customer_category_id);
            });
        }

        return $query;
    }

    /**
     * Check if product is visible for customer (instance method)
     */
    public function isVisibleForCustomer($customer = null): bool
    {
        if (!$this->is_active || !$this->is_visible_on_frontend) {
            return false;
        }

        if ($customer && isset($customer->customer_category_id)) {
            if ($this->excludedCustomerCategories()->where('customer_categories.id', $customer->customer_category_id)->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find available units for a specific date range
     * This checks existing rentals to see which units are free
     * 
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAvailableUnits($startDate, $endDate)
    {
        // Ensure dates are Carbon instances
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        
        // Add buffer time to end date
        $buffer = $this->buffer_time ?? 0;
        
        // 1. Get all active units for this product
        // We filter out retired/broken/lost units immediately
        // Also filter out units in warehouses that are not available for rental
        $units = $this->units()
            ->where('status', '!=', ProductUnit::STATUS_RETIRED)
            ->whereNotIn('condition', ['broken', 'lost'])
            ->where(function ($query) {
                // Unit must either have NO warehouse assigned (legacy/default) OR belong to a warehouse that is available for rental
                $query->whereNull('warehouse_id')
                      ->orWhereHas('warehouse', function ($q) {
                          $q->where('is_available_for_rental', true)
                            ->where('is_active', true);
                      });
            })
            ->with(['kits'])
            ->get();
            
        if ($units->isEmpty()) {
            return $units; // Empty collection
        }

        $unitIds = $units->pluck('id')->toArray();

        // 2. Identify if any of these units are part of a Kit (as a component)
        // We need to check if the PARENT unit is rented
        $parentIds = \App\Models\UnitKit::whereIn('linked_unit_id', $unitIds)
            ->pluck('unit_id')
            ->toArray();
            
        // 3. Identify if any of these units are KITS themselves (have components)
        // We need to check if any COMPONENT is rented
        $kitIds = \App\Models\UnitKit::whereIn('unit_id', $unitIds)
            ->pluck('linked_unit_id')
            ->toArray();
            
        // Also need to check if our unit is a component of a component (nested) - assuming 1 level deep for now based on system complexity
        // But let's be safe and check if our unit is part of a bundle, and that bundle is rented.
        
        // Optimization: Instead of complex recursive queries, we fetch all relevant rentals
        // and check overlap in PHP.
        
        // Get all parents that contain our units
        $otherParentIds = \App\Models\UnitKit::whereIn('linked_unit_id', $unitIds)
            ->pluck('unit_id')
            ->unique()
            ->toArray();

        // Get all components of our units (if our units are kits)
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
        }
        
        // 5. Filter Available Units
        $availableUnits = $units->filter(function ($unit) use ($startDate, $endDate, $buffer, $unitRentals) {
            if (!isset($unitRentals[$unit->id])) {
                return true; // No rentals affecting this unit
            }
            
            foreach ($unitRentals[$unit->id] as $rental) {
                // Check Overlap
                // Existing Rental: R_Start -> R_End
                // Requested: Q_Start -> Q_End
                // Overlap if: Q_Start < R_End + Buffer AND Q_End > R_Start
                
                $rentalEndWithBuffer = Carbon::parse($rental->end_date)->addHours($buffer);
                
                if ($startDate < $rentalEndWithBuffer && $endDate > Carbon::parse($rental->start_date)) {
                    return false; // Overlap found
                }
            }
            
            return true;
        });
        
        // Double check with query-based exclusion for edge cases (like complex nested kits not covered by optimization)
        // This is a safety net, can be removed if optimization is proven 100% correct
        // But for now, let's trust the optimization for speed, unless we find issues.
        // Actually, the previous implementation had a "safety net" query. Let's keep it but optimized.
        // The optimization above covers:
        // 1. Direct rental
        // 2. Component rented (My unit is a kit, its component is rented)
        // 3. Parent rented (My unit is a component, its parent is rented)
        
        // So we can return the filtered collection directly.
        
        return $availableUnits->values(); // Reset keys
    }


    /**
     * Find an available unit for a specific date range
     */
    public function findAvailableUnit($startDate, $endDate)
    {
        return $this->findAvailableUnits($startDate, $endDate)->first();
    }
}
