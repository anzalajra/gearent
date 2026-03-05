<?php

namespace App\Jobs;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Notifications\TenantReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CreateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Cache key prefix for tracking provisioning status.
     */
    public const CACHE_PREFIX = 'tenant_provisioning_';

    /**
     * Cache TTL in seconds (30 minutes).
     */
    public const CACHE_TTL = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $tenantId,
        public string $storeName,
        public string $adminName,
        public string $adminEmail,
        public string $adminPassword,
        public string $domain,
        public string $planSlug = 'free',
        public string $businessCategory = 'other',
    ) {
        $this->onQueue('tenant-creation');
    }

    /**
     * Get the cache key for this tenant's provisioning status.
     */
    public function cacheKey(): string
    {
        return self::CACHE_PREFIX.$this->tenantId;
    }

    /**
     * Update provisioning status in cache.
     */
    protected function setStatus(string $status, array $extra = []): void
    {
        Cache::put($this->cacheKey(), array_merge(['status' => $status], $extra), self::CACHE_TTL);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("CreateTenantJob: Starting creation for tenant '{$this->tenantId}'");

        try {
            // -------------------------------------------------------
            // Step 1: Resolve subscription plan
            // -------------------------------------------------------
            $plan = SubscriptionPlan::active()->where('slug', $this->planSlug)->first()
                ?? SubscriptionPlan::active()->where('slug', 'free')->first();

            $isFreePlan = $plan && $plan->slug === 'free';
            $initialStatus = $isFreePlan ? 'active' : 'trial';
            $trialEndsAt = $isFreePlan ? null : now()->addDays(14);

            // -------------------------------------------------------
            // Step 2: Create the Tenant record in central database.
            //         This triggers the TenancyServiceProvider event pipeline
            //         which synchronously runs: CreateDatabase → MigrateDatabase
            //         → CreateTenantStorageFolder.
            //         Do NOT call Artisan::call('tenants:migrate') here.
            // -------------------------------------------------------
            $this->setStatus('creating_db');

            $tenant = Tenant::create([
                'id' => $this->tenantId,
                'name' => $this->storeName,
                'email' => $this->adminEmail,
                'subscription_plan_id' => $plan?->id,
                'status' => $initialStatus,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => null,
                'current_rental_transactions_month' => 0,
                'current_rental_month' => now()->format('Y-m'),
                'data' => [
                    'business_category' => $this->businessCategory,
                ],
            ]);

            Log::info("CreateTenantJob: Tenant record + DB created: {$tenant->id}");

            // -------------------------------------------------------
            // Step 3: Create the domain for the tenant
            // -------------------------------------------------------
            $tenant->domains()->create([
                'domain' => $this->domain,
            ]);

            Log::info("CreateTenantJob: Domain '{$this->domain}' linked to tenant '{$tenant->id}'");

            // -------------------------------------------------------
            // Step 4: Seed admin user, roles, and default settings
            //         inside the tenant database context.
            // -------------------------------------------------------
            $this->setStatus('creating_admin');

            $tenant->run(function () {
                // Create roles before the admin user
                try {
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'super_admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'staff', 'guard_name' => 'web']
                        );
                        Log::info("CreateTenantJob: Roles created for tenant '{$this->tenantId}'");
                    }
                } catch (\Throwable $e) {
                    Log::warning("CreateTenantJob: Could not create roles: {$e->getMessage()}");
                }

                // Create the admin user
                $user = \App\Models\User::create([
                    'name' => $this->adminName,
                    'email' => $this->adminEmail,
                    'password' => Hash::make($this->adminPassword),
                    'email_verified_at' => now(),
                ]);

                Log::info("CreateTenantJob: Admin user created: {$user->email}");

                // Assign super_admin role
                try {
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super_admin')
                            ->where('guard_name', 'web')
                            ->first();

                        if ($superAdmin) {
                            $user->assignRole($superAdmin);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("CreateTenantJob: Could not assign role: {$e->getMessage()}");
                }

                // Create default settings
                try {
                    if (class_exists(\App\Models\Setting::class) && method_exists(\App\Models\Setting::class, 'set')) {
                        \App\Models\Setting::set('site_name', $this->storeName);
                        \App\Models\Setting::set('currency', 'IDR');
                        \App\Models\Setting::set('timezone', 'Asia/Jakarta');
                    }
                } catch (\Throwable $e) {
                    Log::warning("CreateTenantJob: Could not create settings: {$e->getMessage()}");
                }
            });

            // -------------------------------------------------------
            // Step 5: Mark provisioning as complete
            // -------------------------------------------------------
            $this->setStatus('ready', ['domain' => $this->domain]);

            Log::info("CreateTenantJob: Tenant '{$tenant->id}' is ready at {$this->domain}");

            // Send welcome email
            try {
                Notification::route('mail', $this->adminEmail)
                    ->notify(new TenantReadyNotification($this->storeName, $this->domain, $this->adminEmail));
            } catch (\Throwable $e) {
                Log::warning("CreateTenantJob: Could not send welcome email: {$e->getMessage()}");
            }

        } catch (\Throwable $e) {
            Log::error("CreateTenantJob: Failed for tenant '{$this->tenantId}': {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->setStatus('failed', ['error' => $e->getMessage()]);

            // Clean up on failure
            try {
                $tenant = Tenant::find($this->tenantId);
                if ($tenant) {
                    try {
                        $databaseManager = app(\Stancl\Tenancy\Database\DatabaseManager::class);
                        $databaseManager->delete($tenant);
                    } catch (\Throwable $dbError) {
                        Log::warning("CreateTenantJob: DB cleanup failed: {$dbError->getMessage()}");
                    }

                    $tenant->domains()->delete();
                    $tenant->forceDelete();
                    Log::info("CreateTenantJob: Cleaned up failed tenant '{$this->tenantId}'");
                }
            } catch (\Throwable $cleanupError) {
                Log::error("CreateTenantJob: Cleanup failed: {$cleanupError->getMessage()}");
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure (all retries exhausted).
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("CreateTenantJob: Permanently failed for tenant '{$this->tenantId}'", [
            'error' => $exception?->getMessage(),
        ]);

        $this->setStatus('failed', [
            'error' => $exception?->getMessage() ?? 'Job gagal setelah beberapa percobaan.',
        ]);
    }
}
