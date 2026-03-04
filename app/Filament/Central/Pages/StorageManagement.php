<?php

namespace App\Filament\Central\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use BackedEnum;
use UnitEnum;

class StorageManagement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.central.pages.storage-management';

    public array $storageInfo = [];

    public function mount(): void
    {
        $this->loadStorageInfo();
    }

    protected function loadStorageInfo(): void
    {
        $storagePath = storage_path();
        
        $this->storageInfo = [
            'total_space' => disk_total_space($storagePath),
            'free_space' => disk_free_space($storagePath),
            'used_space' => disk_total_space($storagePath) - disk_free_space($storagePath),
            'storage_path' => $storagePath,
            'public_path' => public_path('storage'),
            'logs_size' => $this->getDirectorySize(storage_path('logs')),
            'cache_size' => $this->getDirectorySize(storage_path('framework/cache')),
            'sessions_size' => $this->getDirectorySize(storage_path('framework/sessions')),
            'views_size' => $this->getDirectorySize(storage_path('framework/views')),
        ];
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        if (!File::isDirectory($path)) {
            return 0;
        }

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function getFormattedStorageInfo(): array
    {
        return [
            'Total Space' => $this->formatBytes($this->storageInfo['total_space']),
            'Free Space' => $this->formatBytes($this->storageInfo['free_space']),
            'Used Space' => $this->formatBytes($this->storageInfo['used_space']),
            'Usage Percentage' => round(($this->storageInfo['used_space'] / $this->storageInfo['total_space']) * 100, 1) . '%',
            'Logs Size' => $this->formatBytes($this->storageInfo['logs_size']),
            'Cache Size' => $this->formatBytes($this->storageInfo['cache_size']),
            'Sessions Size' => $this->formatBytes($this->storageInfo['sessions_size']),
            'Views Cache Size' => $this->formatBytes($this->storageInfo['views_size']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearLogs')
                ->label('Clear Logs')
                ->icon('heroicon-o-document-minus')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will delete all log files. Are you sure?')
                ->action(function () {
                    $logPath = storage_path('logs');
                    $files = File::glob($logPath . '/*.log');
                    
                    foreach ($files as $file) {
                        File::delete($file);
                    }

                    $this->loadStorageInfo();

                    Notification::make()
                        ->success()
                        ->title('Logs Cleared')
                        ->body('All log files have been deleted.')
                        ->send();
                }),

            Action::make('clearSessions')
                ->label('Clear Sessions')
                ->icon('heroicon-o-user-minus')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This will log out all users. Are you sure?')
                ->action(function () {
                    $sessionPath = storage_path('framework/sessions');
                    $files = File::glob($sessionPath . '/*');
                    
                    foreach ($files as $file) {
                        if (File::isFile($file)) {
                            File::delete($file);
                        }
                    }

                    $this->loadStorageInfo();

                    Notification::make()
                        ->success()
                        ->title('Sessions Cleared')
                        ->body('All session files have been deleted.')
                        ->send();
                }),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadStorageInfo();

                    Notification::make()
                        ->success()
                        ->title('Refreshed')
                        ->body('Storage information has been refreshed.')
                        ->send();
                }),
        ];
    }
}
