<?php

namespace App\Jobs;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
     * Create a new job instance.
     */
    public function __construct(
        public string $tenantId,
        public string $storeName,
        public string $adminName,
        public string $adminEmail,
        public string $adminPassword,
        public string $domain,
    ) {
        // Dispatch to Redis queue 'tenant-creation'
        $this->onQueue('tenant-creation');
        $this->onConnection('redis');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("CreateTenantJob: Starting creation for tenant '{$this->tenantId}'");

        try {
            // -------------------------------------------------------
            // Step 1: Create the Tenant record in central database
            // -------------------------------------------------------
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();

            $tenant = Tenant::create([
                'id' => $this->tenantId,
                'name' => $this->storeName,
                'email' => $this->adminEmail,
                'subscription_plan_id' => $freePlan?->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);

            Log::info("CreateTenantJob: Tenant record created: {$tenant->id}");

            // -------------------------------------------------------
            // Step 2: Create the domain for the tenant
            // -------------------------------------------------------
            $tenant->domains()->create([
                'domain' => $this->domain,
            ]);

            Log::info("CreateTenantJob: Domain '{$this->domain}' linked to tenant '{$tenant->id}'");

            // -------------------------------------------------------
            // Step 3: The database is auto-created by stancl/tenancy
            //         via TenantCreated event -> CreateDatabase job
            //         But we need to run migrations explicitly
            // -------------------------------------------------------

            // Step 4: Run tenant migrations
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--force' => true,
            ]);

            Log::info("CreateTenantJob: Migrations completed for tenant '{$tenant->id}'");

            // -------------------------------------------------------
            // Step 5: Seed the admin user inside the tenant database
            // -------------------------------------------------------
            $tenant->run(function () {
                // Create the admin user
                $user = \App\Models\User::create([
                    'name' => $this->adminName,
                    'email' => $this->adminEmail,
                    'password' => Hash::make($this->adminPassword),
                    'email_verified_at' => now(),
                ]);

                Log::info("CreateTenantJob: Admin user created: {$user->email}");

                // Assign super_admin role if Spatie permission is available
                try {
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        // Create roles if they don't exist
                        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'super_admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'staff', 'guard_name' => 'web']
                        );

                        $user->assignRole($superAdmin);
                        Log::info("CreateTenantJob: Role 'super_admin' assigned to {$user->email}");
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
                        Log::info("CreateTenantJob: Default settings created for tenant");
                    }
                } catch (\Throwable $e) {
                    Log::warning("CreateTenantJob: Could not create settings: {$e->getMessage()}");
                }
            });

            // -------------------------------------------------------
            // Step 6: Mark tenant as ready
            // -------------------------------------------------------
            $tenant->update(['status' => 'trial']);

            Log::info("CreateTenantJob: Tenant '{$tenant->id}' is ready at {$this->domain}");

            // TODO: Send welcome email notification to admin
            // Notification::route('mail', $this->adminEmail)->notify(new TenantReadyNotification($tenant));

        } catch (\Throwable $e) {
            Log::error("CreateTenantJob: Failed for tenant '{$this->tenantId}': {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up on failure: delete tenant record if it was created
            try {
                $tenant = Tenant::find($this->tenantId);
                if ($tenant) {
                    // Try to delete the tenant database using tenancy package
                    try {
                        // Use the database manager directly instead of command
                        $databaseManager = app(\Stancl\Tenancy\Database\DatabaseManager::class);
                        $databaseManager->delete($tenant);
                    } catch (\Throwable $dbError) {
                        Log::warning("CreateTenantJob: DB cleanup failed: {$dbError->getMessage()}");
                    }
                    
                    // Delete domains and tenant record
                    $tenant->domains()->delete();
                    $tenant->forceDelete();
                    
                    Log::info("CreateTenantJob: Cleaned up failed tenant '{$this->tenantId}'");
                }
            } catch (\Throwable $cleanupError) {
                Log::error("CreateTenantJob: Cleanup failed: {$cleanupError->getMessage()}");
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("CreateTenantJob: Permanently failed for tenant '{$this->tenantId}'", [
            'error' => $exception?->getMessage(),
        ]);

        // TODO: Notify central admin about failed tenant creation
    }
}
