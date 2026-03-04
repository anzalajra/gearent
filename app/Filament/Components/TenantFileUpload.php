<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;
use App\Services\Storage\TenantStorageService;
use Stancl\Tenancy\Tenancy;
use Closure;

/**
 * Custom FileUpload component for tenant-aware file storage.
 * 
 * Automatically prefixes uploaded files with tenant ID for multi-tenant storage.
 * Example: tenant_toko-a/products/gambar.jpg
 */
class TenantFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default disk to R2
        $this->disk(TenantStorageService::getFilamentDisk());

        // Set default visibility
        $this->visibility(TenantStorageService::getFilamentVisibility());

        // Override directory to include tenant prefix
        $this->directory(function (TenantFileUpload $component): string {
            $baseDirectory = $component->getBaseDirectory();
            return TenantStorageService::getFilamentDirectory($baseDirectory);
        });
    }

    /**
     * Store the base directory without tenant prefix.
     */
    protected string $baseDirectory = '';

    /**
     * Get the base directory (without tenant prefix).
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    /**
     * Set the directory within the tenant's storage space.
     * 
     * @param string|Closure $directory The directory path (e.g., 'products', 'documents')
     */
    public function tenantDirectory(string|Closure $directory): static
    {
        /** @var string $evaluated */
        $evaluated = $this->evaluate($directory);
        $this->baseDirectory = $evaluated;
        
        // Update the actual directory with tenant prefix
        $this->directory(function () use ($directory): string {
            /** @var string $dir */
            $dir = $this->evaluate($directory);
            return TenantStorageService::getFilamentDirectory($dir);
        });

        return $this;
    }

    /**
     * Static helper to create a tenant-aware file upload.
     */
    public static function makeTenant(string $name): static
    {
        return static::make($name);
    }

    /**
     * Make this upload publicly accessible.
     */
    public function publicAccess(): static
    {
        $this->visibility('public');
        return $this;
    }

    /**
     * Make this upload private (default).
     */
    public function privateAccess(): static
    {
        $this->visibility('private');
        return $this;
    }

    /**
     * Get the current tenant ID.
     */
    protected function getCurrentTenantId(): ?string
    {
        if (app()->bound(Tenancy::class)) {
            $tenancy = app(Tenancy::class);
            return $tenancy->tenant?->getTenantKey();
        }

        return null;
    }

    /**
     * Generate a URL for viewing the uploaded file.
     */
    public function getFileUrl(string $file): ?string
    {
        $service = app(TenantStorageService::class);
        
        // Check if file already has full path or needs prefix
        if (str_starts_with($file, 'tenant_') || str_starts_with($file, 'central/')) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = $service->disk();
            return $disk->url($file);
        }

        return $service->url($file);
    }
}
