<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;

class CreateTenantStorageFolder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Tenant $tenant
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if R2 bucket is not configured
        if (!config('filesystems.disks.r2.bucket')) {
            Log::info("CreateTenantStorageFolder: R2 bucket not configured, skipping storage folder creation for tenant '{$this->tenant->id}'");
            return;
        }

        $tenantPrefix = "tenant_{$this->tenant->id}";
        
        // Create base tenant folder with a placeholder file
        // R2/S3 doesn't have real folders, but we can create them by putting files
        $directories = [
            'products',
            'brands',
            'categories',
            'customer-documents',
            'finance/bills',
            'finance/expenses',
            'finance/transactions',
            'documents',
        ];

        try {
            foreach ($directories as $dir) {
                $path = "{$tenantPrefix}/{$dir}/.gitkeep";
                
                if (!Storage::disk('r2')->exists($path)) {
                    Storage::disk('r2')->put($path, '');
                }
            }
            
            Log::info("CreateTenantStorageFolder: Created storage folders for tenant '{$this->tenant->id}'");
        } catch (\Throwable $e) {
            Log::warning("CreateTenantStorageFolder: Failed to create storage folders for tenant '{$this->tenant->id}': {$e->getMessage()}");
            // Don't throw - this is not critical for tenant creation
        }
    }
}
