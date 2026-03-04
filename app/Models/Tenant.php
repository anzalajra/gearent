<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * Custom columns that should be stored directly in the database
     * rather than in the 'data' JSON column.
     *
     * @return array
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
        return $this->onTrial();
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
}
