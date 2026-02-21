<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class SystemCheckup extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'System Checkup & Fix';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.clusters.settings.pages.system-checkup';

    // Progress tracking properties
    public bool $isProcessing = false;
    public string $currentOperation = '';
    public string $progressMessage = '';
    public int $progressPercent = 0;
    
    // Operation history
    public array $operationHistory = [];

    protected function logOperation(string $operation, string $status, string $message = ''): void
    {
        $this->operationHistory[] = [
            'operation' => $operation,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
        
        // Keep only last 10 operations
        $this->operationHistory = array_slice($this->operationHistory, -10);
    }

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
            'operationHistory' => array_reverse(array_slice($this->operationHistory, -5)), // Show last 5 operations
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->disabled(fn() => $this->isProcessing)
                ->action(function () {
                    // Reset progress state
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function optimizeClearAction(): Action
    {
        return Action::make('optimize_clear')
            ->label('Clear Cache (Artisan)')
            ->icon('heroicon-o-trash')
            ->color('warning')
            ->disabled(fn() => $this->isProcessing)
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Clearing system cache...';
                $this->progressMessage = 'Starting cache clearing process...';
                $this->progressPercent = 10;
                
                try {
                    // Simulate progress for better UX
                    $this->progressMessage = 'Clearing configuration cache...';
                    $this->progressPercent = 30;
                    
                    Artisan::call('config:clear');
                    
                    $this->progressMessage = 'Clearing route cache...';
                    $this->progressPercent = 50;
                    
                    Artisan::call('route:clear');
                    
                    $this->progressMessage = 'Clearing view cache...';
                    $this->progressPercent = 70;
                    
                    Artisan::call('view:clear');
                    
                    $this->progressMessage = 'Clearing application cache...';
                    $this->progressPercent = 90;
                    
                    Artisan::call('cache:clear');
                    
                    $this->progressMessage = 'Finalizing...';
                    $this->progressPercent = 100;
                    
                    // Final optimize clear
                    Artisan::call('optimize:clear');
                    
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    $this->logOperation('Cache Clear', 'success', 'All cache types cleared successfully');
                    
                    Notification::make()
                        ->title('System optimized and cache cleared')
                        ->body('All cache types have been successfully cleared.')
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Notification::make()
                        ->title('Cache clearing failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function runMigrationsAction(): Action
    {
        return Action::make('run_migrations')
            ->label('Run Database Migrations')
            ->icon('heroicon-o-play')
            ->color('primary')
            ->disabled(fn() => $this->isProcessing)
            ->requiresConfirmation()
            ->modalHeading('Run Migrations')
            ->modalDescription('This will attempt to run "php artisan migrate --force".')
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Running database migrations...';
                $this->progressMessage = 'Checking for pending migrations...';
                $this->progressPercent = 10;
                
                try {
                    Log::info('Starting manual migration via SystemCheckup...');
                    
                    $this->progressMessage = 'Running migration command...';
                    $this->progressPercent = 50;
                    
                    Artisan::call('migrate', ['--force' => true]);
                    $output = Artisan::output();
                    
                    $this->progressMessage = 'Finalizing migration...';
                    $this->progressPercent = 90;
                    
                    Log::info('Migration output: ' . $output);
                    
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;

                    $this->logOperation('Database Migration', 'success', 'Migrations executed successfully');

                    Notification::make()
                        ->title('Migrations executed successfully')
                        ->body($output ? nl2br(e($output)) : 'All migrations have been applied successfully.')
                        ->success()
                        ->persistent()
                        ->send();
                        
                } catch (\Throwable $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Log::error('Migration failed: ' . $e->getMessage());
                    
                    Notification::make()
                        ->title('Migration failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    public function viewMigrationSqlAction(): Action
    {
        return Action::make('view_migration_sql')
            ->label('Get SQL Query')
            ->icon('heroicon-o-code-bracket')
            ->color('gray')
            ->modalHeading('Pending Migration SQL')
            ->modalDescription('Copy this SQL and run it in phpMyAdmin if the automatic migration fails.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->form([
                Textarea::make('sql_query')
                    ->label('SQL Query')
                    ->rows(15)
                    ->readonly()
                    ->default(function () {
                        try {
                            Artisan::call('migrate', ['--force' => true, '--pretend' => true]);
                            return Artisan::output() ?: 'No pending migrations found.';
                        } catch (\Throwable $e) {
                            return 'Error generating SQL: ' . $e->getMessage();
                        }
                    }),
            ])
            ->action(function () {
                // No action needed, just viewing
            });
    }

    public function deepCleanCacheAction(): Action
    {
        return Action::make('deep_clean_cache')
            ->label('Force Delete Cache Files')
            ->icon('heroicon-o-fire')
            ->color('danger')
            ->disabled(fn() => $this->isProcessing)
            ->requiresConfirmation()
            ->modalHeading('Deep Clean Cache')
            ->modalDescription('This will manually delete files in bootstrap/cache and storage/framework/views. Use this if artisan commands fail.')
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Force deleting cache files...';
                $this->progressMessage = 'Starting deep cache cleaning...';
                $this->progressPercent = 10;
                
                try {
                    $this->progressMessage = 'Deleting bootstrap cache files...';
                    $this->progressPercent = 30;
                    
                    $files = [
                        base_path('bootstrap/cache/routes-v7.php'),
                        base_path('bootstrap/cache/config.php'),
                        base_path('bootstrap/cache/packages.php'),
                        base_path('bootstrap/cache/services.php'),
                    ];

                    $deletedCount = 0;
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            @unlink($file);
                            $deletedCount++;
                        }
                    }

                    $this->progressMessage = 'Deleting view cache files...';
                    $this->progressPercent = 60;
                    
                    $viewFiles = glob(storage_path('framework/views/*'));
                    $viewDeletedCount = 0;
                    foreach ($viewFiles as $file) {
                        if (is_file($file) && basename($file) !== '.gitignore') {
                            @unlink($file);
                            $viewDeletedCount++;
                        }
                    }

                    $this->progressMessage = 'Finalizing cleanup...';
                    $this->progressPercent = 90;
                    
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;

                    $this->logOperation('Deep Cache Clean', 'success', "Deleted {$deletedCount} bootstrap and {$viewDeletedCount} view cache files");

                    Notification::make()
                        ->title('Cache files forcefully deleted')
                        ->body("Deleted {$deletedCount} bootstrap cache files and {$viewDeletedCount} view cache files.")
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Notification::make()
                        ->title('Deep cache cleaning failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function fixStorageLinkAction(): Action
    {
        return Action::make('fix_storage_link')
            ->label('Fix Storage Link')
            ->icon('heroicon-o-link')
            ->color('primary')
            ->disabled(fn() => $this->isProcessing)
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Fixing storage link...';
                $this->progressMessage = 'Checking current storage link status...';
                $this->progressPercent = 10;
                
                try {
                    $link = public_path('storage');
                    
                    $this->progressMessage = 'Backing up existing directory...';
                    $this->progressPercent = 30;
                    
                    if (file_exists($link) && is_dir($link) && !is_link($link)) {
                        $backupPath = $link . '_backup_' . date('Ymd_His');
                        rename($link, $backupPath);
                        $this->progressMessage = 'Existing directory backed up to: ' . basename($backupPath);
                    }
                    
                    $this->progressMessage = 'Creating storage symlink...';
                    $this->progressPercent = 60;
                    
                    Artisan::call('storage:link');
                    
                    $this->progressMessage = 'Verifying symlink...';
                    $this->progressPercent = 90;
                    
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;

                    $this->logOperation('Storage Link Fix', 'success', 'Storage symlink created successfully');

                    Notification::make()
                        ->title('Storage link fixed successfully')
                        ->body('The storage symlink has been created successfully.')
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Notification::make()
                        ->title('Storage link fix failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function cleanLogsAction(): Action
    {
        return Action::make('clean_logs')
            ->label('Truncate Logs')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->disabled(fn() => $this->isProcessing)
            ->requiresConfirmation()
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Truncating log files...';
                $this->progressMessage = 'Checking log file status...';
                $this->progressPercent = 10;
                
                try {
                    $logPath = storage_path('logs/laravel.log');
                    
                    if (file_exists($logPath)) {
                        $this->progressMessage = 'Reading current log file size...';
                        $this->progressPercent = 30;
                        
                        $originalSize = filesize($logPath);
                        $sizeInMB = round($originalSize / 1024 / 1024, 2);
                        
                        $this->progressMessage = 'Truncating log file...';
                        $this->progressPercent = 60;
                        
                        file_put_contents($logPath, '');
                        
                        $this->progressMessage = 'Verifying truncation...';
                        $this->progressPercent = 90;
                        
                        $this->isProcessing = false;
                        $this->currentOperation = '';
                        $this->progressMessage = '';
                        $this->progressPercent = 0;

                        $this->logOperation('Log Cleanup', 'success', "Cleared {$sizeInMB} MB of log data");

                        Notification::make()
                            ->title('Logs truncated successfully')
                            ->body("Cleared {$sizeInMB} MB of log data.")
                            ->success()
                            ->send();
                    } else {
                        $this->isProcessing = false;
                        $this->currentOperation = '';
                        $this->progressMessage = '';
                        $this->progressPercent = 0;
                        
                        Notification::make()
                            ->title('No log file found')
                            ->info()
                            ->send();
                    }
                    
                } catch (\Exception $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Notification::make()
                        ->title('Log truncation failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function retryFailedJobsAction(): Action
    {
        return Action::make('retry_failed_jobs')
            ->label('Retry Failed Jobs')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->disabled(fn() => $this->isProcessing)
            ->requiresConfirmation()
            ->action(function () {
                $this->isProcessing = true;
                $this->currentOperation = 'Retrying failed jobs...';
                $this->progressMessage = 'Counting failed jobs...';
                $this->progressPercent = 10;
                
                try {
                    // Count failed jobs before retry
                    $failedCount = DB::table('failed_jobs')->count();
                    
                    if ($failedCount === 0) {
                        $this->isProcessing = false;
                        $this->currentOperation = '';
                        $this->progressMessage = '';
                        $this->progressPercent = 0;
                        
                        Notification::make()
                            ->title('No failed jobs to retry')
                            ->info()
                            ->send();
                        return;
                    }
                    
                    $this->progressMessage = "Retrying {$failedCount} failed jobs...";
                    $this->progressPercent = 50;
                    
                    Artisan::call('queue:retry', ['all' => true]);
                    
                    $this->progressMessage = 'Verifying retry status...';
                    $this->progressPercent = 80;
                    
                    // Count remaining failed jobs
                    $remainingCount = DB::table('failed_jobs')->count();
                    $processedCount = $failedCount - $remainingCount;
                    
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    $this->logOperation('Retry Failed Jobs', 'success', "Retried {$processedCount} jobs");
                    
                    Notification::make()
                        ->title('Job retry process completed')
                        ->body("Retried {$processedCount} jobs. {$remainingCount} jobs remaining.")
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    $this->isProcessing = false;
                    $this->currentOperation = '';
                    $this->progressMessage = '';
                    $this->progressPercent = 0;
                    
                    Notification::make()
                        ->title('Job retry failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
