<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\Storage\R2StorageService;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use BackedEnum;
use UnitEnum;

class R2FileBrowser extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'File Browser';
    protected static ?string $title = 'R2 File Browser';
    protected static ?int $navigationSort = 101;
    protected string $view = 'filament.central.pages.r2-file-browser';

    #[Url]
    public string $currentPath = '';

    public array $breadcrumbs = [];
    public array $tenantStats = [];
    public Collection $items;
    public array $selectedItems = [];

    public function mount(): void
    {
        $this->items = collect();
        $this->buildBreadcrumbs();
        $this->loadTenantStats();
        $this->loadItems();
    }

    protected function buildBreadcrumbs(): void
    {
        $this->breadcrumbs = [];
        
        if (empty($this->currentPath)) {
            return;
        }

        $parts = explode('/', $this->currentPath);
        $path = '';

        foreach ($parts as $part) {
            $path = $path ? "{$path}/{$part}" : $part;
            $this->breadcrumbs[] = [
                'name' => $part,
                'path' => $path,
            ];
        }
    }

    protected function loadTenantStats(): void
    {
        $service = app(R2StorageService::class);
        $this->tenantStats = $service->getTenantStorageStats()->toArray();
    }

    protected function loadItems(): void
    {
        $service = app(R2StorageService::class);
        $this->items = $service->listAll($this->currentPath);
    }

    public function navigateTo(string $path): void
    {
        $this->currentPath = $path;
        $this->buildBreadcrumbs();
        $this->loadItems();
        $this->selectedItems = [];
    }

    public function goBack(): void
    {
        if (empty($this->currentPath)) {
            return;
        }

        $parts = explode('/', $this->currentPath);
        array_pop($parts);
        $this->currentPath = implode('/', $parts);
        $this->buildBreadcrumbs();
        $this->loadItems();
        $this->selectedItems = [];
    }

    public function goToRoot(): void
    {
        $this->currentPath = '';
        $this->breadcrumbs = [];
        $this->loadItems();
        $this->selectedItems = [];
    }

    protected function getFileIcon(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'heroicon-o-photo',
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx' => 'heroicon-o-document',
            'xls', 'xlsx', 'csv' => 'heroicon-o-table-cells',
            'zip', 'rar', '7z', 'tar', 'gz' => 'heroicon-o-archive-box',
            'mp4', 'avi', 'mov', 'webm' => 'heroicon-o-film',
            'mp3', 'wav', 'ogg' => 'heroicon-o-musical-note',
            default => 'heroicon-o-document',
        };
    }

    public function getDownloadUrl(string $path): ?string
    {
        $service = app(R2StorageService::class);
        return $service->getTemporaryUrl($path, 60);
    }

    public function deleteItem(string $path, string $type): void
    {
        $service = app(R2StorageService::class);
        
        $success = $type === 'directory'
            ? $service->deleteDirectory($path)
            : $service->deleteFile($path);

        if ($success) {
            Notification::make()
                ->title('Item berhasil dihapus')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal menghapus item')
                ->danger()
                ->send();
        }

        $this->loadTenantStats();
        $this->loadItems();
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedItems)) {
            return;
        }

        $service = app(R2StorageService::class);
        $deleted = 0;

        foreach ($this->selectedItems as $itemPath) {
            $item = $this->items->firstWhere('path', $itemPath);
            if (!$item) continue;

            $success = $item['type'] === 'directory'
                ? $service->deleteDirectory($item['path'])
                : $service->deleteFile($item['path']);

            if ($success) {
                $deleted++;
            }
        }

        Notification::make()
            ->title("{$deleted} item berhasil dihapus")
            ->success()
            ->send();

        $this->selectedItems = [];
        $this->loadTenantStats();
        $this->loadItems();
    }

    public function getTenants(): array
    {
        return Tenant::all()
            ->mapWithKeys(fn ($tenant) => [
                "tenant_{$tenant->id}" => $tenant->name ?? $tenant->id
            ])
            ->prepend('Central (System)', 'central')
            ->toArray();
    }

    public function jumpToTenant(string $tenantPath): void
    {
        if (empty($tenantPath)) {
            return;
        }
        $this->navigateTo($tenantPath);
    }

    public function refresh(): void
    {
        $this->loadTenantStats();
        $this->loadItems();
        
        Notification::make()
            ->title('Data diperbarui')
            ->success()
            ->send();
    }

    public function isImage(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }
}
