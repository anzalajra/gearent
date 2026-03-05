<?php

namespace App\Livewire;

use App\Jobs\CreateTenantJob;
use App\Models\Domain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.guest')]
class RegisterTenant extends Component
{
    // Step tracking
    public int $currentStep = 1;

    // Step 1: User Data
    public string $admin_name = '';

    public string $admin_email = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Step 2: Business Info
    public string $store_name = '';

    public string $subdomain = '';

    public string $business_category = '';

    public string $selected_plan_slug = 'free';

    // Step 3: Provisioning
    public bool $submitted = false;

    public string $tenantDomain = '';

    // Provisioning status (read from Cache)
    public string $provisioningStatus = 'queued'; // queued | creating_db | creating_admin | ready | failed

    public string $provisioningError = '';

    public int $provisioningProgress = 5;

    public string $provisioningStep = 'Menunggu antrian...';

    /** @var array<int, array<string, mixed>> */
    public array $plans = [];

    /**
     * Reserved subdomains that cannot be registered.
     */
    protected array $reservedSubdomains = [
        'admin', 'www', 'api', 'mail', 'ftp', 'pop', 'smtp', 'imap',
        'app', 'dev', 'staging', 'test', 'beta', 'demo', 'central',
        'zewalo', 'static', 'cdn', 'assets', 'media', 'dashboard',
        'panel', 'console', 'billing', 'auth', 'login', 'register',
        'support', 'help', 'docs', 'status', 'blog', 'shop', 'store',
    ];

    public function mount(): void
    {
        $this->plans = SubscriptionPlan::active()
            ->whereIn('slug', ['free', 'basic', 'pro'])
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $current = collect($this->plans)->firstWhere('slug', $this->selected_plan_slug);
        if (! $current) {
            $first = collect($this->plans)->first();
            if ($first) {
                $this->selected_plan_slug = $first['slug'];
            }
        }
    }

    /**
     * Auto-generate subdomain from store name.
     */
    public function updatedStoreName(string $value): void
    {
        if (empty($this->subdomain)) {
            $this->subdomain = Str::slug($value);
        }
    }

    /**
     * Real-time email duplicate check.
     */
    public function updatedAdminEmail(string $value): void
    {
        if (! empty($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->resetErrorBag('admin_email');
            if (Tenant::where('email', $value)->exists()) {
                $this->addError('admin_email', 'Email ini sudah terdaftar sebagai pemilik toko lain.');
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
            'subdomain.alpha_dash' => 'Subdomain hanya boleh huruf, angka, dan tanda hubung.',
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

            // Check email not already used by another tenant
            $this->validateAdminEmail();

            if ($this->getErrorBag()->isEmpty()) {
                $this->currentStep = 2;
            }

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
     * Validate that the admin email is not already registered as a tenant owner.
     */
    protected function validateAdminEmail(): void
    {
        if (Tenant::where('email', $this->admin_email)->exists()) {
            $this->addError('admin_email', 'Email ini sudah terdaftar sebagai pemilik toko lain.');
        }
    }

    /**
     * Validate subdomain: uniqueness + reserved names.
     */
    protected function validateSubdomain(): void
    {
        $slug = Str::slug($this->subdomain);

        // Reserved subdomains
        if (in_array($slug, $this->reservedSubdomains, true)) {
            $this->addError('subdomain', 'Subdomain ini tidak tersedia. Pilih nama lain.');

            return;
        }

        $fullDomain = $slug.'.'.config('app.domain', 'zewalo.test');

        if (Domain::where('domain', $fullDomain)->exists()) {
            $this->addError('subdomain', 'Subdomain sudah digunakan. Pilih yang lain.');

            return;
        }

        if (Tenant::find($slug)) {
            $this->addError('subdomain', 'Subdomain sudah digunakan. Pilih yang lain.');
        }
    }

    /**
     * Dispatch the tenant creation job and start polling.
     * The HTTP request returns immediately; provisioning runs in the background.
     */
    public function register(): void
    {
        // Rate limiting: 1 registration attempt per IP per 60 seconds as requested
        $rateLimitKey = 'register-tenant:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('subdomain', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");
            $this->currentStep = 2;

            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        $baseDomain = config('app.domain', 'zewalo.test');
        $fullDomain = Str::slug($this->subdomain).'.'.$baseDomain;
        $tenantId = Str::slug($this->subdomain);
        $cacheKey = CreateTenantJob::CACHE_PREFIX.$tenantId;

        // Seed initial cache status so polling works immediately
        Cache::put($cacheKey, ['status' => 'queued'], CreateTenantJob::CACHE_TTL);

        // Dispatch to background Redis queue — HTTP request returns now
        CreateTenantJob::dispatch(
            tenantId: $tenantId,
            storeName: $this->store_name,
            adminName: $this->admin_name,
            adminEmail: $this->admin_email,
            adminPassword: $this->password,
            domain: $fullDomain,
            planSlug: $this->selected_plan_slug,
            businessCategory: $this->business_category,
        );

        $this->tenantDomain = $fullDomain;
        $this->submitted = true;
        $this->provisioningStatus = 'queued';
        $this->provisioningStep = 'Menunggu antrian...';
        $this->provisioningProgress = 5;
    }

    /**
     * Poll provisioning status from Cache (called by wire:poll).
     * Stops updating once status is 'ready' or 'failed'.
     */
    public function checkProvisioningStatus(): void
    {
        if (! $this->submitted) {
            return;
        }

        if (in_array($this->provisioningStatus, ['ready', 'failed'], true)) {
            return;
        }

        $cacheKey = CreateTenantJob::CACHE_PREFIX.Str::slug($this->subdomain);
        $data = Cache::get($cacheKey);

        if (! $data) {
            return;
        }

        $status = $data['status'] ?? 'queued';

        match ($status) {
            'queued' => [
                $this->provisioningStatus = 'queued',
                $this->provisioningStep = 'Menunggu antrian...',
                $this->provisioningProgress = 5,
            ],
            'creating_db' => [
                $this->provisioningStatus = 'creating_db',
                $this->provisioningStep = 'Membuat database & menjalankan migrasi...',
                $this->provisioningProgress = 35,
            ],
            'creating_admin' => [
                $this->provisioningStatus = 'creating_admin',
                $this->provisioningStep = 'Membuat akun admin & pengaturan awal...',
                $this->provisioningProgress = 75,
            ],
            'ready' => [
                $this->provisioningStatus = 'ready',
                $this->provisioningStep = 'Selesai!',
                $this->provisioningProgress = 100,
                $this->tenantDomain = $data['domain'] ?? $this->tenantDomain,
            ],
            'failed' => [
                $this->provisioningStatus = 'failed',
                $this->provisioningStep = 'Gagal membuat tenant.',
                $this->provisioningProgress = 0,
                $this->provisioningError = $data['error'] ?? 'Terjadi kesalahan yang tidak diketahui.',
            ],
            default => null,
        };
    }

    /**
     * Retry provisioning after a failure.
     */
    public function retryRegistration(): void
    {
        // Clear old cache
        Cache::forget(CreateTenantJob::CACHE_PREFIX.Str::slug($this->subdomain));

        $this->provisioningStatus = 'queued';
        $this->provisioningError = '';
        $this->provisioningProgress = 5;
        $this->provisioningStep = 'Menunggu antrian...';

        $this->register();
    }

    public function render()
    {
        return view('livewire.register-tenant');
    }
}
