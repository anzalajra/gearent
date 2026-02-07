<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class SystemCheckup extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?string $title = 'System Checkup & Fix';

    protected string $view = 'filament.pages.system-checkup';

    protected function getViewData(): array
    {
        return [
            'dbStatus' => $this->checkDatabase(),
            'storageStatus' => $this->checkStorage(),
            'cacheStatus' => $this->checkCache(),
            'symlinkStatus' => $this->checkSymlink(),
            'logStatus' => $this->checkLogs(),
            'queueStatus' => $this->checkFailedJobs(),
            'phpVersion' => phpversion(),
            'laravelVersion' => app()->version(),
            'storageUsage' => $this->checkStorageUsage(),
            'databaseUsage' => $this->checkDatabaseSize(),
        ];
    }

    protected function checkStorageUsage()
    {
        try {
            $path = storage_path('app/public');
            if (!file_exists($path)) {
                return '0 MB';
            }
            
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                $size += $file->getSize();
            }
            
            return round($size / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function checkDatabaseSize()
    {
        try {
            $dbName = DB::connection()->getDatabaseName();
            $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
            
            return ($result[0]->size ?? 0) . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Connected', 'color' => 'success'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'color' => 'danger'];
        }
    }

    protected function checkStorage()
    {
        if (is_writable(storage_path())) {
            return ['status' => 'ok', 'message' => 'Writable', 'color' => 'success'];
        }
        return ['status' => 'error', 'message' => 'Not Writable', 'color' => 'danger'];
    }

    protected function checkCache()
    {
        try {
            Cache::put('system_check', 'ok', 10);
            if (Cache::get('system_check') === 'ok') {
                return ['status' => 'ok', 'message' => 'Working', 'color' => 'success'];
            }
        } catch (\Exception $e) {
            // ignore
        }
        return ['status' => 'error', 'message' => 'Not Working', 'color' => 'danger'];
    }

    protected function checkSymlink()
    {
        $link = public_path('storage');
        if (is_link($link)) {
            $target = readlink($link);
            if (file_exists($link)) { // check if link target exists
                 return ['status' => 'ok', 'message' => 'Linked correctly', 'color' => 'success'];
            }
             return ['status' => 'error', 'message' => 'Link broken', 'color' => 'danger'];
        }
        
        if (file_exists($link) && is_dir($link)) {
             return ['status' => 'error', 'message' => 'Directory exists (not link)', 'color' => 'danger'];
        }

        return ['status' => 'warning', 'message' => 'Missing', 'color' => 'warning'];
    }

    protected function checkLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $size = filesize($logPath);
            $sizeMb = round($size / 1024 / 1024, 2);
            if ($sizeMb > 50) {
                 return ['status' => 'warning', 'message' => "Large Log File ({$sizeMb}MB)", 'color' => 'warning'];
            }
            return ['status' => 'ok', 'message' => "Normal ({$sizeMb}MB)", 'color' => 'success'];
        }
        return ['status' => 'ok', 'message' => 'No Log File', 'color' => 'success'];
    }

    protected function checkFailedJobs()
    {
        try {
            $count = DB::table('failed_jobs')->count();
            if ($count > 0) {
                 return ['status' => 'warning', 'message' => "{$count} Failed Jobs", 'color' => 'warning'];
            }
            return ['status' => 'ok', 'message' => 'No Failed Jobs', 'color' => 'success'];
        } catch (\Exception $e) {
             return ['status' => 'error', 'message' => 'Queue Check Failed', 'color' => 'danger'];
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->action('$refresh'),
        ];
    }

    public function optimizeClearAction(): Action
    {
        return Action::make('optimize_clear')
            ->label('Clear Cache (Artisan)')
            ->icon('heroicon-o-trash')
            ->color('warning')
            ->action(function () {
                Artisan::call('optimize:clear');
                Notification::make()->title('System optimized and cache cleared')->success()->send();
            });
    }

    public function deepCleanCacheAction(): Action
    {
        return Action::make('deep_clean_cache')
            ->label('Force Delete Cache Files')
            ->icon('heroicon-o-fire')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Deep Clean Cache')
            ->modalDescription('This will manually delete files in bootstrap/cache and storage/framework/views. Use this if artisan commands fail.')
            ->action(function () {
                $files = [
                    base_path('bootstrap/cache/routes-v7.php'),
                    base_path('bootstrap/cache/config.php'),
                    base_path('bootstrap/cache/packages.php'),
                    base_path('bootstrap/cache/services.php'),
                ];

                foreach ($files as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }

                $viewFiles = glob(storage_path('framework/views/*'));
                foreach ($viewFiles as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore') {
                        @unlink($file);
                    }
                }

                Notification::make()->title('Cache files forcefully deleted')->success()->send();
            });
    }

    public function fixStorageLinkAction(): Action
    {
        return Action::make('fix_storage_link')
            ->label('Fix Storage Link')
            ->icon('heroicon-o-link')
            ->color('primary')
            ->action(function () {
                $link = public_path('storage');
                if (file_exists($link) && is_dir($link) && !is_link($link)) {
                        rename($link, $link . '_backup_' . date('Ymd_His'));
                }
                Artisan::call('storage:link');
                Notification::make()->title('Storage link fixed')->success()->send();
            });
    }

    public function cleanLogsAction(): Action
    {
        return Action::make('clean_logs')
            ->label('Truncate Logs')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function () {
                    $logPath = storage_path('logs/laravel.log');
                    if (file_exists($logPath)) {
                        file_put_contents($logPath, '');
                        Notification::make()->title('Logs truncated')->success()->send();
                    } else {
                        Notification::make()->title('No log file found')->info()->send();
                    }
            });
    }

    public function retryFailedJobsAction(): Action
    {
        return Action::make('retry_failed_jobs')
            ->label('Retry Failed Jobs')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function () {
                    Artisan::call('queue:retry', ['all' => true]);
                    Notification::make()->title('Failed jobs queued for retry')->success()->send();
            });
    }
}
