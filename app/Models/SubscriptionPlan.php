<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /**
     * The connection name for the model.
     * Always use central database.
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_users',
        'max_products',
        'max_storage_mb',
        'max_domains',
        'max_rental_transactions_per_month',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_users' => 'integer',
        'max_products' => 'integer',
        'max_storage_mb' => 'integer',
        'max_domains' => 'integer',
        'max_rental_transactions_per_month' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the tenants for this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get storage limit in human readable format.
     */
    public function getStorageLimitAttribute(): string
    {
        if ($this->max_storage_mb >= 1024) {
            return round($this->max_storage_mb / 1024, 1) . ' GB';
        }
        return $this->max_storage_mb . ' MB';
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
