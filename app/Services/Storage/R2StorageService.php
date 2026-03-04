<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Models\Tenant;

class R2StorageService
{
    protected string $disk = 'r2';

    /**
     * Test the R2 connection.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'R2 belum dikonfigurasi. Silakan isi kredensial R2 terlebih dahulu.',
            ];
        }

        try {
            // Try to list files in root to test connection
            Storage::disk($this->disk)->directories('/');
            
            return [
                'success' => true,
                'message' => 'Koneksi ke Cloudflare R2 berhasil!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get R2 storage health information.
     */
    public function getHealthInfo(): array
    {
        try {
            $connectionTest = $this->testConnection();
            
            $config = config('filesystems.disks.r2');
            
            return [
                'status' => $connectionTest['success'] ? 'healthy' : 'error',
                'connection' => $connectionTest,
                'bucket' => $config['bucket'] ?? 'Not configured',
                'endpoint' => $config['endpoint'] ?? 'Not configured',
                'region' => $config['region'] ?? 'auto',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'connection' => [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Get storage statistics.
     */
    public function getStorageStats(): array
    {
        if (!$this->isConfigured()) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'total_size_formatted' => '0 B',
                'error' => 'R2 belum dikonfigurasi',
            ];
        }

        try {
            $allFiles = Storage::disk($this->disk)->allFiles('/');
            $totalSize = 0;
            
            foreach ($allFiles as $file) {
                try {
                    $totalSize += Storage::disk($this->disk)->size($file);
                } catch (\Exception $e) {
                    continue;
                }
            }

            return [
                'total_files' => count($allFiles),
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
            ];
        } catch (\Exception $e) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'total_size_formatted' => '0 B',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get storage statistics per tenant.
     */
    public function getTenantStorageStats(): Collection
    {
        $stats = collect();

        if (!$this->isConfigured()) {
            return $stats;
        }

        try {
            $directories = Storage::disk($this->disk)->directories('/');
            
            foreach ($directories as $dir) {
                // Check if it's a tenant directory
                if (str_starts_with(basename($dir), 'tenant_')) {
                    $tenantId = str_replace('tenant_', '', basename($dir));
                    $files = Storage::disk($this->disk)->allFiles($dir);
                    $size = 0;

                    foreach ($files as $file) {
                        try {
                            $size += Storage::disk($this->disk)->size($file);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    $tenant = Tenant::find($tenantId);

                    $stats->push([
                        'tenant_id' => $tenantId,
                        'tenant_name' => $tenant?->name ?? $tenantId,
                        'files_count' => count($files),
                        'size' => $size,
                        'size_formatted' => $this->formatBytes($size),
                        'directory' => $dir,
                    ]);
                }
            }

            // Add central storage if exists
            if (in_array('central', $directories)) {
                $files = Storage::disk($this->disk)->allFiles('central');
                $size = 0;

                foreach ($files as $file) {
                    try {
                        $size += Storage::disk($this->disk)->size($file);
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                $stats->push([
                    'tenant_id' => 'central',
                    'tenant_name' => 'Central (System)',
                    'files_count' => count($files),
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'directory' => 'central',
                ]);
            }
        } catch (\Exception $e) {
            // Return empty collection on error
        }

        return $stats->sortByDesc('size')->values();
    }

    /**
     * List files in a directory.
     */
    public function listFiles(string $directory = ''): Collection
    {
        if (!$this->isConfigured()) {
            return collect();
        }

        try {
            $files = Storage::disk($this->disk)->files($directory);
            
            return collect($files)->map(function ($file) {
                try {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk($this->disk);
                    return [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => $disk->size($file),
                        'size_formatted' => $this->formatBytes($disk->size($file)),
                        'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                        'url' => $disk->url($file),
                        'type' => 'file',
                        'extension' => pathinfo($file, PATHINFO_EXTENSION),
                    ];
                } catch (\Exception $e) {
                    return [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => 0,
                        'size_formatted' => 'N/A',
                        'last_modified' => 'N/A',
                        'url' => null,
                        'type' => 'file',
                        'extension' => pathinfo($file, PATHINFO_EXTENSION),
                    ];
                }
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * List directories in a path.
     */
    public function listDirectories(string $directory = ''): Collection
    {
        if (!$this->isConfigured()) {
            return collect();
        }

        try {
            $directories = Storage::disk($this->disk)->directories($directory);
            
            return collect($directories)->map(function ($dir) {
                return [
                    'name' => basename($dir),
                    'path' => $dir,
                    'type' => 'directory',
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * List all items (files and directories) in a path.
     */
    public function listAll(string $directory = ''): Collection
    {
        $directories = $this->listDirectories($directory);
        $files = $this->listFiles($directory);

        return $directories->merge($files);
    }

    /**
     * Delete a file.
     */
    public function deleteFile(string $path): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->delete($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a directory and all its contents.
     */
    public function deleteDirectory(string $path): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->deleteDirectory($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get file contents.
     */
    public function getFileContents(string $path): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            return Storage::disk($this->disk)->get($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get temporary URL for file download.
     */
    public function getTemporaryUrl(string $path, int $minutes = 60): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk($this->disk);
            return $disk->temporaryUrl(
                $path,
                now()->addMinutes($minutes)
            );
        } catch (\Exception $e) {
            // If temporary URLs are not supported, return regular URL
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk($this->disk);
                return $disk->url($path);
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    /**
     * Create a directory.
     */
    public function createDirectory(string $path): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->makeDirectory($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get the current R2 configuration.
     */
    public function getConfiguration(): array
    {
        $config = config('filesystems.disks.r2', []);
        
        return [
            'key' => $config['key'] ?? '',
            'secret' => $config['secret'] ? '********' : 'Not set',
            'region' => $config['region'] ?? 'auto',
            'bucket' => $config['bucket'] ?? '',
            'endpoint' => $config['endpoint'] ?? '',
            'url' => $config['url'] ?? '',
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? true,
        ];
    }

    /**
     * Check if R2 is configured.
     */
    public function isConfigured(): bool
    {
        $config = config('filesystems.disks.r2', []);
        
        return !empty($config['key']) 
            && !empty($config['secret']) 
            && !empty($config['bucket']) 
            && !empty($config['endpoint']);
    }
}
