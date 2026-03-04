<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Stancl\Tenancy\Tenancy;

class TenantStorageService
{
    protected string $disk = 'r2';
    protected ?string $tenantId = null;

    public function __construct()
    {
        $this->tenantId = $this->getCurrentTenantId();
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
     * Get the tenant prefix for storage paths.
     */
    public function getTenantPrefix(?string $tenantId = null): string
    {
        $id = $tenantId ?? $this->tenantId;
        
        if (!$id) {
            return 'central';
        }

        return 'tenant_' . $id;
    }

    /**
     * Get the full path with tenant prefix.
     */
    public function getPath(string $path, ?string $tenantId = null): string
    {
        $prefix = $this->getTenantPrefix($tenantId);
        return $prefix . '/' . ltrim($path, '/');
    }

    /**
     * Store a file with tenant prefix.
     */
    public function store(UploadedFile $file, string $directory = '', ?string $filename = null): string
    {
        $path = $this->getPath($directory);
        
        if ($filename) {
            return Storage::disk($this->disk)->putFileAs($path, $file, $filename);
        }

        return Storage::disk($this->disk)->putFile($path, $file);
    }

    /**
     * Store file contents with tenant prefix.
     */
    public function put(string $path, $contents, array $options = []): bool
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->put($fullPath, $contents, $options);
    }

    /**
     * Get a file from storage.
     */
    public function get(string $path): ?string
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->get($fullPath);
    }

    /**
     * Get the URL for a file.
     */
    public function url(string $path): string
    {
        $fullPath = $this->getPath($path);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);
        return $disk->url($fullPath);
    }

    /**
     * Get temporary URL (signed) for a file.
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration): string
    {
        $fullPath = $this->getPath($path);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);
        return $disk->temporaryUrl($fullPath, $expiration);
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->delete($fullPath);
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->exists($fullPath);
    }

    /**
     * List all files in a directory.
     */
    public function files(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getPath($directory);
        
        if ($recursive) {
            return Storage::disk($this->disk)->allFiles($fullPath);
        }

        return Storage::disk($this->disk)->files($fullPath);
    }

    /**
     * List all directories in a path.
     */
    public function directories(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getPath($directory);
        
        if ($recursive) {
            return Storage::disk($this->disk)->allDirectories($fullPath);
        }

        return Storage::disk($this->disk)->directories($fullPath);
    }

    /**
     * Get file size in bytes.
     */
    public function size(string $path): int
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->size($fullPath);
    }

    /**
     * Get file last modified timestamp.
     */
    public function lastModified(string $path): int
    {
        $fullPath = $this->getPath($path);
        return Storage::disk($this->disk)->lastModified($fullPath);
    }

    /**
     * Copy a file to a new location.
     */
    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->getPath($from);
        $toPath = $this->getPath($to);
        return Storage::disk($this->disk)->copy($fromPath, $toPath);
    }

    /**
     * Move a file to a new location.
     */
    public function move(string $from, string $to): bool
    {
        $fromPath = $this->getPath($from);
        $toPath = $this->getPath($to);
        return Storage::disk($this->disk)->move($fromPath, $toPath);
    }

    /**
     * Get the storage disk instance.
     */
    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Set custom disk.
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Set custom tenant ID (for central admin operations).
     */
    public function forTenant(?string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Get the Filament upload directory with tenant prefix.
     */
    public static function getFilamentDirectory(string $directory = ''): string
    {
        $service = app(self::class);
        return $service->getPath($directory);
    }

    /**
     * Get the Filament visibility setting.
     */
    public static function getFilamentVisibility(): string
    {
        return 'private';
    }

    /**
     * Get the Filament disk setting.
     */
    public static function getFilamentDisk(): string
    {
        return 'r2';
    }
}
