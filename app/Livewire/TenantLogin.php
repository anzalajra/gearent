<?php

namespace App\Livewire;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.guest')]
class TenantLogin extends Component
{
    public string $input = '';

    /** @var array{name: string, id: string, status: string}|null */
    public ?array $foundTenant = null;

    public string $foundDomain = '';

    public string $errorMessage = '';

    public bool $isSearching = false;

    public bool $isSuspended = false;

    public int $rateLimitSeconds = 0;

    /**
     * Search for a tenant by subdomain or admin email.
     */
    public function searchTenant(): void
    {
        $this->validate([
            'input' => 'required|string|min:2|max:150',
        ], [
            'input.required' => 'Masukkan subdomain atau email toko Anda.',
            'input.min' => 'Minimal 2 karakter.',
        ]);

        // Rate limiting: max 5 searches per 5 minutes per IP
        $rateLimitKey = 'tenant-login:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->rateLimitSeconds = RateLimiter::availableIn($rateLimitKey);
            $this->errorMessage = "Terlalu banyak percobaan. Coba lagi dalam {$this->rateLimitSeconds} detik.";

            return;
        }
        RateLimiter::hit($rateLimitKey, 300);

        $this->isSearching = true;
        $this->errorMessage = '';
        $this->foundTenant = null;
        $this->isSuspended = false;

        $input = trim($this->input);
        $tenant = null;
        $domainStr = null;
        $baseDomain = config('app.domain', 'zewalo.com');

        if (str_contains($input, '@')) {
            // Search by admin email
            $tenant = Tenant::where('email', $input)->with('domains')->first();
            if ($tenant) {
                $domainStr = $tenant->domains()->value('domain');
            }
        } else {
            // Search by subdomain (with or without the base domain)
            $full = str_contains($input, '.') ? $input : ($input.'.'.$baseDomain);
            $domainModel = Domain::where('domain', $full)->with('tenant')->first();
            if ($domainModel) {
                $tenant = $domainModel->tenant;
                $domainStr = $domainModel->domain;
            }
        }

        $this->isSearching = false;

        if (! $tenant) {
            $this->errorMessage = 'Toko tidak ditemukan. Periksa kembali subdomain atau email Anda.';

            return;
        }

        if ($tenant->status === 'suspended') {
            $this->isSuspended = true;
            $this->foundTenant = ['name' => $tenant->name, 'id' => $tenant->id, 'status' => 'suspended'];

            return;
        }

        $this->foundTenant = [
            'name' => $tenant->name,
            'id' => $tenant->id,
            'status' => $tenant->status,
        ];
        $this->foundDomain = $domainStr ?? ($tenant->id.'.'.$baseDomain);
    }

    /**
     * Redirect user to the tenant's admin login page.
     */
    public function redirectToTenant(): void
    {
        if (! $this->foundDomain) {
            return;
        }

        $this->redirect('http://'.$this->foundDomain.'/admin/login');
    }

    /**
     * Reset search state.
     */
    public function reset(...$properties): void
    {
        $this->foundTenant = null;
        $this->foundDomain = '';
        $this->errorMessage = '';
        $this->isSuspended = false;
        $this->isSearching = false;
        $this->rateLimitSeconds = 0;
        $this->input = '';
    }

    public function render()
    {
        return view('livewire.tenant-login');
    }
}
