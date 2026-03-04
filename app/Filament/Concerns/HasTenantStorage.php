<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use App\Services\Storage\TenantStorageService;

/**
 * Trait to provide tenant-aware file upload helpers for Filament Resources.
 * 
 * Usage in your Resource form:
 * ```php
 * use App\Filament\Concerns\HasTenantStorage;
 * 
 * class ProductResource extends Resource
 * {
 *     use HasTenantStorage;
 * 
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             static::tenantFileUpload('image', 'products'),
 *             // or
 *             FileUpload::make('image')->tenantDirectory('products'),
 *         ]);
 *     }
 * }
 * ```
 */
trait HasTenantStorage
{
    /**
     * Create a tenant-aware file upload field.
     *
     * @param string $name The field name
     * @param string $directory The directory within tenant storage (e.g., 'products', 'documents')
     * @param bool $image Whether this is an image upload
     * @return FileUpload
     */
    protected static function tenantFileUpload(
        string $name,
        string $directory = '',
        bool $image = true
    ): FileUpload {
        $upload = FileUpload::make($name)
            ->disk(TenantStorageService::getFilamentDisk())
            ->visibility(TenantStorageService::getFilamentVisibility())
            ->directory(fn () => TenantStorageService::getFilamentDirectory($directory));

        if ($image) {
            $upload->image()
                ->imageEditor()
                ->imageEditorAspectRatios([
                    null,
                    '16:9',
                    '4:3',
                    '1:1',
                ]);
        }

        return $upload;
    }

    /**
     * Create a tenant-aware multiple file upload field.
     *
     * @param string $name The field name
     * @param string $directory The directory within tenant storage
     * @param int $maxFiles Maximum number of files
     * @return FileUpload
     */
    protected static function tenantMultipleFileUpload(
        string $name,
        string $directory = '',
        int $maxFiles = 10
    ): FileUpload {
        return FileUpload::make($name)
            ->disk(TenantStorageService::getFilamentDisk())
            ->visibility(TenantStorageService::getFilamentVisibility())
            ->directory(fn () => TenantStorageService::getFilamentDirectory($directory))
            ->multiple()
            ->maxFiles($maxFiles)
            ->reorderable()
            ->appendFiles();
    }

    /**
     * Create a tenant-aware document upload field.
     *
     * @param string $name The field name
     * @param string $directory The directory within tenant storage
     * @param array $acceptedTypes Accepted file types
     * @return FileUpload
     */
    protected static function tenantDocumentUpload(
        string $name,
        string $directory = 'documents',
        array $acceptedTypes = ['application/pdf', 'image/*']
    ): FileUpload {
        return FileUpload::make($name)
            ->disk(TenantStorageService::getFilamentDisk())
            ->visibility(TenantStorageService::getFilamentVisibility())
            ->directory(fn () => TenantStorageService::getFilamentDirectory($directory))
            ->acceptedFileTypes($acceptedTypes)
            ->maxSize(10240); // 10MB
    }

    /**
     * Get the URL for a file stored in tenant storage.
     */
    protected static function getTenantFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $service = app(TenantStorageService::class);
        return $service->url($path);
    }

    /**
     * Get a temporary URL for a file stored in tenant storage.
     */
    protected static function getTenantTemporaryUrl(?string $path, int $minutes = 60): ?string
    {
        if (empty($path)) {
            return null;
        }

        $service = app(TenantStorageService::class);
        return $service->temporaryUrl($path, now()->addMinutes($minutes));
    }
}
