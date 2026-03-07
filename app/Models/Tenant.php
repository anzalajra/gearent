<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantFeature;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The connection name for the model.
     * Always use central database.
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'subscription_plan_id',
        'trial_ends_at',
        'subscription_ends_at',
        'grace_period_ends_at',
        'status',
        'current_rental_transactions_month',
        'current_rental_month',
        'data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
    ];

    /**
     * Custom columns that should be stored directly in the database
     * rather than in the 'data' JSON column.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'subscription_plan_id',
            'trial_ends_at',
            'subscription_ends_at',
            'grace_period_ends_at',
            'status',
            'current_rental_transactions_month',
            'current_rental_month',
        ];
    }

    /**
     * Get the subscription plan for the tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function saasInvoices(): HasMany
    {
        return $this->hasMany(SaasInvoice::class);
    }

    public function activeSubscription(): ?TenantSubscription
    {
        return $this->tenantSubscriptions()->active()->latest('started_at')->first();
    }

    /**
     * Check if tenant is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    /**
     * Check if tenant subscription is active.
     */
    public function subscriptionActive(): bool
    {
        if ($this->status === 'active') {
            return $this->subscription_ends_at === null || $this->subscription_ends_at->isFuture();
        }

        if ($this->isInGracePeriod()) {
            return true;
        }

        return $this->onTrial();
    }

    /**
     * Check if tenant is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace_period'
            && $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if tenant is currently on the Free plan.
     */
    public function isOnFreePlan(): bool
    {
        return optional($this->subscriptionPlan)->slug === 'free';
    }

    /**
     * Get the configured monthly rental transaction limit for this tenant.
     */
    public function rentalLimit(): ?int
    {
        return $this->subscriptionPlan?->max_rental_transactions_per_month;
    }

    /**
     * Get remaining rental transactions for the current month.
     * Returns null when unlimited.
     */
    public function remainingRentalTransactions(): ?int
    {
        $limit = $this->rentalLimit();

        if ($limit === null) {
            return null;
        }

        $used = (int) $this->current_rental_transactions_month;

        return max(0, $limit - $used);
    }

    /**
     * Check if a feature is enabled for this tenant.
     *
     * Priority: feature_overrides (bidirectional) > subscription plan features.
     */
    public function hasFeature(TenantFeature $feature): bool
    {
        $overrides = $this->feature_overrides ?? [];

        if (is_array($overrides) && array_key_exists($feature->value, $overrides)) {
            return (bool) $overrides[$feature->value];
        }

        $plan = $this->subscriptionPlan;

        if ($plan && is_array($plan->features)) {
            return in_array($feature->value, $plan->features);
        }

        return false;
    }
}
