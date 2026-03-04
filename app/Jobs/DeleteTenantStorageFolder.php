<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;

class DeleteTenantStorageFolder implements ShouldQueue
{
    use Queueable;

    protected string $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant)
    {
        // Store the tenant ID since the tenant model might be deleted
        $this->tenantId = $tenant->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if R2 bucket is not configured
        if (!config('filesystems.disks.r2.bucket')) {
            Log::info("DeleteTenantStorageFolder: R2 bucket not configured, skipping storage folder deletion for tenant '{$this->tenantId}'");
            return;
        }

        $tenantPrefix = "tenant_{$this->tenantId}";
        
        try {
            // Delete all files in the tenant's storage folder
            Storage::disk('r2')->deleteDirectory($tenantPrefix);
            Log::info("DeleteTenantStorageFolder: Deleted storage folders for tenant '{$this->tenantId}'");
        } catch (\Throwable $e) {
            Log::warning("DeleteTenantStorageFolder: Failed to delete storage folders for tenant '{$this->tenantId}': {$e->getMessage()}");
            // Don't throw - this is not critical
        }
    }
}
