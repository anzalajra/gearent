<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SaasInvoiceStatus;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSubscriptionInvoices extends Command
{
    protected $signature = 'subscriptions:generate-invoices';

    protected $description = 'Generate SaaS invoices for tenants with subscriptions expiring within 7 days';

    public function handle(): int
    {
        $this->info('Checking for tenants needing subscription invoices...');

        $tenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now()->addDays(7))
            ->where('subscription_ends_at', '>', now())
            ->whereHas('subscriptionPlan', fn ($q) => $q->where('price_monthly', '>', 0)->orWhere('price_yearly', '>', 0))
            ->with(['subscriptionPlan', 'tenantSubscriptions' => fn ($q) => $q->active()->latest('started_at')])
            ->get();

        $generated = 0;

        foreach ($tenants as $tenant) {
            // Check if invoice already exists for the upcoming period
            $existingInvoice = SaasInvoice::where('tenant_id', $tenant->id)
                ->whereIn('status', [SaasInvoiceStatus::Pending, SaasInvoiceStatus::Paid])
                ->where('due_at', '>=', now())
                ->exists();

            if ($existingInvoice) {
                $this->line("  Skipping {$tenant->name} — invoice already exists");

                continue;
            }

            $plan = $tenant->subscriptionPlan;
            $activeSubscription = $tenant->tenantSubscriptions->first();
            $billingCycle = $activeSubscription?->billing_cycle ?? 'monthly';
            $price = $billingCycle === 'yearly' ? (float) $plan->price_yearly : (float) $plan->price_monthly;

            if ($price <= 0) {
                continue;
            }

            // Create next subscription period
            $nextStart = $tenant->subscription_ends_at;
            $nextEnd = $billingCycle === 'yearly'
                ? $nextStart->copy()->addYear()
                : $nextStart->copy()->addMonth();

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'previous_plan_id' => $activeSubscription?->subscription_plan_id,
                'started_at' => $nextStart,
                'ends_at' => $nextEnd,
                'status' => 'pending_payment',
                'price' => $price,
                'currency' => $plan->currency,
                'billing_cycle' => $billingCycle,
            ]);

            // Create invoice
            $invoice = SaasInvoice::create([
                'tenant_id' => $tenant->id,
                'tenant_subscription_id' => $subscription->id,
                'amount' => $price,
                'tax' => 0,
                'total' => $price,
                'currency' => $plan->currency,
                'status' => SaasInvoiceStatus::Pending,
                'issued_at' => now(),
                'due_at' => $tenant->subscription_ends_at,
            ]);

            $this->info("  Invoice {$invoice->invoice_number} generated for {$tenant->name} — Rp ".number_format($price, 0, ',', '.'));

            Log::info('Subscription invoice generated', [
                'tenant' => $tenant->id,
                'invoice' => $invoice->invoice_number,
                'amount' => $price,
                'due_at' => $invoice->due_at->toDateString(),
            ]);

            $generated++;
        }

        $this->info("Done. {$generated} invoice(s) generated.");

        return self::SUCCESS;
    }
}
