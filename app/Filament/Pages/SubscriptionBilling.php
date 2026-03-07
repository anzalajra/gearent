<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\SaasInvoice;
use App\Models\User;
use Filament\Pages\Page;

class SubscriptionBilling extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Subscription & Billing';

    protected string $view = 'filament.pages.subscription-billing';

    public ?string $planName = null;

    public ?string $planStatus = null;

    public ?string $statusColor = null;

    public ?string $expiresAt = null;

    public ?string $trialEndsAt = null;

    public array $usageStats = [];

    public $invoices = [];

    public function mount(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            return;
        }

        $tenant->load('subscriptionPlan');

        $this->planName = $tenant->subscriptionPlan?->name ?? 'No Plan';
        $this->planStatus = ucfirst($tenant->status);
        $this->statusColor = match ($tenant->status) {
            'active' => 'success',
            'trial' => 'warning',
            'suspended' => 'danger',
            default => 'gray',
        };

        $this->expiresAt = $tenant->subscription_ends_at?->format('d M Y');
        $this->trialEndsAt = $tenant->trial_ends_at?->format('d M Y');

        $plan = $tenant->subscriptionPlan;

        $this->usageStats = [
            [
                'label' => 'Users',
                'used' => User::count(),
                'limit' => $plan?->max_users,
            ],
            [
                'label' => 'Products',
                'used' => Product::count(),
                'limit' => $plan?->max_products,
            ],
            [
                'label' => 'Rental Transactions (this month)',
                'used' => $tenant->current_rental_transactions_month ?? 0,
                'limit' => $plan?->max_rental_transactions_per_month,
            ],
            [
                'label' => 'Storage',
                'used' => null,
                'limit' => $plan?->max_storage_mb,
                'suffix' => 'MB',
            ],
        ];

        $this->invoices = SaasInvoice::where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->limit(20)
            ->get();
    }
}
