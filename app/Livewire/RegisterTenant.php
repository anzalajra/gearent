<?php

namespace App\Livewire;

use App\Jobs\CreateTenantJob;
use App\Models\Domain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('livewire.layouts.guest')]
class RegisterTenant extends Component
{
    // Step tracking
    public int $currentStep = 1;

    // Step 1: User Data
    #[Validate('required|string|max:255')]
    public string $admin_name = '';

    #[Validate('required|email|max:255')]
    public string $admin_email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    // Step 2: Business Info
    #[Validate('required|string|max:255')]
    public string $store_name = '';

    #[Validate('required|string|max:63|alpha_dash|unique:domains,domain')]
    public string $subdomain = '';

    #[Validate('required|string')]
    public string $business_category = '';

    // Step 3: Provisioning
    public bool $submitted = false;
    public string $tenantDomain = '';

    // Plan selection
    #[Validate('required|exists:subscription_plans,slug')]
    public string $selected_plan_slug = 'free';

    /**
     * Cached list of active plans (Free, Basic, Pro).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $plans = [];
    
    // Real provisioning status
    public string $provisioningStatus = 'pending'; // pending, creating, ready, failed
    public string $provisioningError = '';
    public int $provisioningProgress = 0;
    public string $provisioningStep = '';

    /**
     * Auto-generate subdomain from store name.
     */
    public function updatedStoreName(string $value): void
    {
        if (empty($this->subdomain) || $this->subdomain === Str::slug(old('store_name', ''))) {
            $this->subdomain = Str::slug($value);
        }
    }

    public function mount(): void
    {
        $this->plans = SubscriptionPlan::active()
            ->whereIn('slug', ['free', 'basic', 'pro'])
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        // Ensure selected plan is a valid active plan
        $current = collect($this->plans)->firstWhere('slug', $this->selected_plan_slug);
        if (! $current) {
            $first = collect($this->plans)->first();
            if ($first) {
                $this->selected_plan_slug = $first['slug'];
            }
        }
    }

    /**
     * Sanitize subdomain on update.
     */
    public function updatedSubdomain(string $value): void
    {
        $this->subdomain = Str::slug($value);
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'admin_name.required' => 'Nama lengkap wajib diisi.',
            'admin_email.required' => 'Email wajib diisi.',
            'admin_email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'store_name.required' => 'Nama toko wajib diisi.',
            'subdomain.required' => 'Subdomain wajib diisi.',
            'subdomain.unique' => 'Subdomain sudah digunakan. Pilih yang lain.',
            'subdomain.alpha_dash' => 'Subdomain hanya boleh huruf, angka, dash, dan underscore.',
            'business_category.required' => 'Kategori bisnis wajib dipilih.',
        ];
    }

    /**
     * Move to next step with validation.
     */
    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);
            $this->currentStep = 2;
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'store_name' => 'required|string|max:255',
                'subdomain' => 'required|string|max:63|alpha_dash',
                'business_category' => 'required|string',
                'selected_plan_slug' => 'required|exists:subscription_plans,slug',
            ]);
            $this->validateSubdomain();
            
            if ($this->getErrorBag()->isEmpty()) {
                $this->currentStep = 3;
                $this->register();
            }
        }
    }

    /**
     * Move to previous step.
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Validate subdomain uniqueness against domains table.
     */
    protected function validateSubdomain(): void
    {
        $fullDomain = $this->subdomain . '.' . config('app.domain', 'zewalo.test');

        // Check if domain already exists
        if (Domain::where('domain', $fullDomain)->exists()) {
            $this->addError('subdomain', 'Subdomain sudah digunakan. Pilih yang lain.');
            return;
        }

        // Check if tenant ID already exists
        if (Tenant::find($this->subdomain)) {
            $this->addError('subdomain', 'Subdomain sudah digunakan. Pilih yang lain.');
            return;
        }
    }

    /**
     * Submit the registration and start provisioning synchronously.
     * This runs the tenant creation process directly for immediate feedback.
     */
    public function register(): void
    {
        $baseDomain = config('app.domain', 'zewalo.test');
        $fullDomain = $this->subdomain . '.' . $baseDomain;
        
        $this->tenantDomain = $fullDomain;
        $this->submitted = true;
        $this->provisioningStatus = 'creating';
        $this->provisioningStep = 'Membuat database tenant...';
        $this->provisioningProgress = 10;

        // Resolve selected plan (fallback to Free if something goes wrong)
        $plan = SubscriptionPlan::active()
            ->where('slug', $this->selected_plan_slug)
            ->first()
            ?? SubscriptionPlan::active()->where('slug', 'free')->first();

        $isFreePlan = $plan && $plan->slug === 'free';
        $initialStatus = $isFreePlan ? 'active' : 'trial';
        $trialEndsAt = $isFreePlan ? null : now()->addDays(14);

        try {
            // Step 1: Create the Tenant record
            $this->provisioningStep = 'Membuat database tenant...';
            $this->provisioningProgress = 20;
            
            $tenant = Tenant::create([
                'id' => $this->subdomain,
                'name' => $this->store_name,
                'email' => $this->admin_email,
                'subscription_plan_id' => $plan?->id,
                'status' => $initialStatus,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => null,
                'current_rental_transactions_month' => 0,
                'current_rental_month' => now()->format('Y-m'),
            ]);

            Log::info("RegisterTenant: Tenant record created: {$tenant->id}");
            $this->provisioningProgress = 40;

            // Step 2: Create the domain
            $this->provisioningStep = 'Menyiapkan domain...';
            $tenant->domains()->create([
                'domain' => $fullDomain,
            ]);

            Log::info("RegisterTenant: Domain '{$fullDomain}' linked to tenant '{$tenant->id}'");
            $this->provisioningProgress = 60;

            // Step 3: Run tenant migrations (database is auto-created by TenantCreated event)
            $this->provisioningStep = 'Menjalankan migrasi database...';
            // Note: Migrations are handled by TenantCreated event listener in TenancyServiceProvider
            // The JobPipeline will run: CreateDatabase, MigrateDatabase, CreateTenantStorageFolder
            $this->provisioningProgress = 80;

            // Step 4: Create admin user inside tenant database
            $this->provisioningStep = 'Membuat akun admin...';
            $tenant->run(function () {
                // First, create required roles (needed before User is created due to User model's booted event)
                try {
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'super_admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'admin', 'guard_name' => 'web']
                        );
                        \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => 'staff', 'guard_name' => 'web']
                        );
                        Log::info("RegisterTenant: Roles created in tenant database");
                    }
                } catch (\Throwable $e) {
                    Log::warning("RegisterTenant: Could not create roles: {$e->getMessage()}");
                }

                // Create the admin user
                $user = \App\Models\User::create([
                    'name' => $this->admin_name,
                    'email' => $this->admin_email,
                    'password' => Hash::make($this->password),
                    'email_verified_at' => now(),
                ]);

                Log::info("RegisterTenant: Admin user created: {$user->email}");

                // Assign super_admin role
                try {
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super_admin')
                            ->where('guard_name', 'web')
                            ->first();

                        if ($superAdmin) {
                            $user->assignRole($superAdmin);
                            Log::info("RegisterTenant: Role 'super_admin' assigned to {$user->email}");
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("RegisterTenant: Could not assign role: {$e->getMessage()}");
                }

                // Create default settings
                try {
                    if (class_exists(\App\Models\Setting::class) && method_exists(\App\Models\Setting::class, 'set')) {
                        \App\Models\Setting::set('site_name', $this->store_name);
                        \App\Models\Setting::set('currency', 'IDR');
                        \App\Models\Setting::set('timezone', 'Asia/Jakarta');
                        Log::info("RegisterTenant: Default settings created");
                    }
                } catch (\Throwable $e) {
                    Log::warning("RegisterTenant: Could not create settings: {$e->getMessage()}");
                }
            });

            $this->provisioningProgress = 95;

            // Step 5: Mark tenant as ready
            $this->provisioningStep = 'Finalisasi...';
            $this->provisioningProgress = 100;
            $this->provisioningStatus = 'ready';
            $this->provisioningStep = 'Selesai!';

            Log::info("RegisterTenant: Tenant '{$tenant->id}' is ready at {$fullDomain}");

        } catch (\Throwable $e) {
            Log::error("RegisterTenant: Failed for tenant '{$this->subdomain}': {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->provisioningStatus = 'failed';
            $this->provisioningError = $e->getMessage();
            $this->provisioningStep = 'Gagal membuat tenant';

            // Try to clean up
            try {
                $tenant = Tenant::find($this->subdomain);
                if ($tenant) {
                    $tenant->domains()->delete();
                    $tenant->forceDelete();
                }
            } catch (\Throwable $cleanupError) {
                Log::error("RegisterTenant: Cleanup failed: {$cleanupError->getMessage()}");
            }
        }
    }

    /**
     * Retry tenant creation after failure.
     */
    public function retryRegistration(): void
    {
        $this->provisioningStatus = 'pending';
        $this->provisioningError = '';
        $this->provisioningProgress = 0;
        $this->provisioningStep = '';
        $this->register();
    }

    public function render()
    {
        return view('livewire.register-tenant');
    }
}
