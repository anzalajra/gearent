<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SaasInvoiceStatus;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus extends Command
{
    protected $signature = 'subscriptions:check-status';

    protected $description = 'Check and update tenant subscription statuses (grace period, suspension, overdue invoices)';

    public function handle(): int
    {
        $this->info('Checking subscription statuses...');

        $this->transitionToGracePeriod();
        $this->transitionToSuspended();
        $this->handleTrialExpiry();
        $this->markOverdueInvoices();

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Active tenants with expired subscriptions → grace_period (7 days).
     */
    protected function transitionToGracePeriod(): void
    {
        $tenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->whereHas('subscriptionPlan', fn ($q) => $q->where('price_monthly', '>', 0)->orWhere('price_yearly', '>', 0))
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->update([
                'status' => 'grace_period',
                'grace_period_ends_at' => now()->addDays(7),
            ]);

            $this->warn("  {$tenant->name} → grace_period (until ".$tenant->grace_period_ends_at->format('d M Y').')');

            Log::info('Tenant moved to grace period', [
                'tenant' => $tenant->id,
                'grace_until' => $tenant->grace_period_ends_at->toDateString(),
            ]);
        }
    }

    /**
     * Grace period tenants with expired grace → suspended.
     */
    protected function transitionToSuspended(): void
    {
        $tenants = Tenant::where('status', 'grace_period')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->update([
                'status' => 'suspended',
            ]);

            $this->error("  {$tenant->name} → suspended");

            Log::warning('Tenant suspended due to non-payment', [
                'tenant' => $tenant->id,
            ]);
        }
    }

    /**
     * Trial tenants with expired trials.
     */
    protected function handleTrialExpiry(): void
    {
        $tenants = Tenant::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->with('subscriptionPlan')
            ->get();

        foreach ($tenants as $tenant) {
            $isFreePlan = $tenant->isOnFreePlan();

            if ($isFreePlan) {
                $tenant->update(['status' => 'active']);
                $this->info("  {$tenant->name} (free plan) → active");
            } else {
                $tenant->update([
                    'status' => 'grace_period',
                    'grace_period_ends_at' => now()->addDays(7),
                ]);
                $this->warn("  {$tenant->name} (trial expired, paid plan) → grace_period");
            }
        }
    }

    /**
     * Mark pending invoices past due date as overdue.
     */
    protected function markOverdueInvoices(): void
    {
        $count = SaasInvoice::where('status', SaasInvoiceStatus::Pending)
            ->where('due_at', '<', now())
            ->update(['status' => SaasInvoiceStatus::Overdue]);

        if ($count > 0) {
            $this->info("  {$count} invoice(s) marked as overdue");
        }
    }
}
