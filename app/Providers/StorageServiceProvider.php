<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Storage\TenantStorageService;
use App\Services\Storage\R2StorageService;
use Filament\Forms\Components\FileUpload;
use Stancl\Tenancy\Tenancy;
use Closure;

class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantStorageService::class, function ($app) {
            return new TenantStorageService();
        });

        $this->app->singleton(R2StorageService::class, function ($app) {
            return new R2StorageService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->extendFilamentFileUpload();
    }

    /**
     * Extend Filament's FileUpload with tenant-aware methods.
     */
    protected function extendFilamentFileUpload(): void
    {
        // Add macro for tenant-aware directory
        FileUpload::macro('tenantDirectory', function (string|Closure $directory) {
            /** @var FileUpload $this */
            $component = $this;
            
            // Set disk to R2
            $component->disk(TenantStorageService::getFilamentDisk());
            
            // Set default visibility
            $component->visibility(TenantStorageService::getFilamentVisibility());
            
            // Set directory with tenant prefix
            $component->directory(function () use ($directory, $component): string {
                $dir = $directory instanceof Closure ? $directory() : $directory;
                return TenantStorageService::getFilamentDirectory($dir);
            });

            return $component;
        });

        // Add macro for using R2 storage without tenant prefix (for central admin)
        FileUpload::macro('r2Directory', function (string|Closure $directory) {
            /** @var FileUpload $this */
            $component = $this;
            
            $component->disk('r2');
            $component->visibility('private');
            
            $component->directory(function () use ($directory): string {
                return $directory instanceof Closure ? $directory() : $directory;
            });

            return $component;
        });

        // Add macro to use R2 storage with auto tenant prefix detection
        FileUpload::macro('r2Tenant', function (string|Closure $directory = '') {
            /** @var FileUpload $this */
            $component = $this;

            $component->disk('r2');
            $component->visibility('private');
            
            $component->directory(function () use ($directory): string {
                $tenantId = null;
                
                if (app()->bound(Tenancy::class)) {
                    $tenancy = app(Tenancy::class);
                    $tenantId = $tenancy->tenant?->getTenantKey();
                }

                $prefix = $tenantId ? "tenant_{$tenantId}" : 'central';
                $dir = $directory instanceof Closure ? $directory() : $directory;
                
                return $dir ? "{$prefix}/{$dir}" : $prefix;
            });

            return $component;
        });
    }
}
