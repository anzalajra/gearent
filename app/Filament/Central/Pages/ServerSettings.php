<?php

namespace App\Filament\Central\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class ServerSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.central.pages.server-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application Settings')
                    ->description('Read-only overview of current application configuration')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Application Name')
                            ->disabled(),

                        TextInput::make('app_env')
                            ->label('Environment')
                            ->disabled(),

                        Toggle::make('app_debug')
                            ->label('Debug Mode')
                            ->disabled(),

                        TextInput::make('app_url')
                            ->label('Application URL')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Database & Cache')
                    ->schema([
                        TextInput::make('db_connection')
                            ->label('Database Driver')
                            ->disabled(),

                        TextInput::make('cache_driver')
                            ->label('Cache Driver')
                            ->disabled(),

                        TextInput::make('session_driver')
                            ->label('Session Driver')
                            ->disabled(),

                        TextInput::make('queue_connection')
                            ->label('Queue Driver')
                            ->disabled(),
                    ])
                    ->columns(2),

            ])
            ->statePath('data');
    }

    protected function getViewData(): array
    {
        return [
            'systemInfo' => [
                'PHP Version' => phpversion(),
                'Laravel Version' => app()->version(),
            ],
            'healthChecks' => [
                'Storage Permissions' => $this->checkStoragePermissions(),
                'Cache' => $this->checkCache(),
                'Storage Symlink' => $this->checkSymlink(),
                'Failed Jobs' => $this->checkFailedJobs(),
            ],
        ];
    }

    protected function checkStoragePermissions(): array
    {
        if (is_writable(storage_path())) {
            return ['status' => 'ok', 'message' => 'Writable', 'icon' => 'heroicon-o-check-circle', 'color' => 'success'];
        }

        return ['status' => 'error', 'message' => 'Not Writable', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger'];
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('system_check', 'ok', 10);
            if (Cache::get('system_check') === 'ok') {
                return ['status' => 'ok', 'message' => 'Working', 'icon' => 'heroicon-o-check-circle', 'color' => 'success'];
            }
        } catch (\Exception $e) {
            // ignore
        }

        return ['status' => 'error', 'message' => 'Not Working', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger'];
    }

    protected function checkSymlink(): array
    {
        $link = public_path('storage');
        if (is_link($link) && file_exists($link)) {
            return ['status' => 'ok', 'message' => 'Linked', 'icon' => 'heroicon-o-check-circle', 'color' => 'success'];
        }

        if (is_link($link)) {
            return ['status' => 'error', 'message' => 'Broken Link', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger'];
        }

        return ['status' => 'warning', 'message' => 'Missing', 'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'warning'];
    }

    protected function checkFailedJobs(): array
    {
        try {
            $count = DB::connection('central')->table('failed_jobs')->count();
            if ($count > 0) {
                return ['status' => 'warning', 'message' => "{$count} Failed", 'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'warning'];
            }

            return ['status' => 'ok', 'message' => 'None', 'icon' => 'heroicon-o-check-circle', 'color' => 'success'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Check Failed', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger'];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');

                    Notification::make()
                        ->success()
                        ->title('Cache Cleared')
                        ->body('All caches have been cleared successfully.')
                        ->send();
                }),

            Action::make('deepCleanCache')
                ->label('Deep Clean')
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Deep Clean Cache')
                ->modalDescription('This will manually delete files in bootstrap/cache and storage/framework/views. Use if artisan commands fail.')
                ->action(function () {
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

                    $viewFiles = glob(storage_path('framework/views/*'));
                    $viewDeletedCount = 0;
                    foreach ($viewFiles as $file) {
                        if (is_file($file) && basename($file) !== '.gitignore') {
                            @unlink($file);
                            $viewDeletedCount++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Deep Cache Clean Complete')
                        ->body("Deleted {$deletedCount} bootstrap and {$viewDeletedCount} view cache files.")
                        ->send();
                }),

            Action::make('optimizeApp')
                ->label('Optimize')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('optimize');

                    Notification::make()
                        ->success()
                        ->title('Application Optimized')
                        ->body('Application has been optimized for production.')
                        ->send();
                }),

            Action::make('fixStorageLink')
                ->label('Fix Storage Link')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn () => $this->checkSymlink()['status'] !== 'ok')
                ->action(function () {
                    $link = public_path('storage');

                    if (file_exists($link) && is_dir($link) && ! is_link($link)) {
                        $backupPath = $link.'_backup_'.date('Ymd_His');
                        rename($link, $backupPath);
                    }

                    Artisan::call('storage:link');

                    Notification::make()
                        ->success()
                        ->title('Storage Link Fixed')
                        ->body('The storage symlink has been created successfully.')
                        ->send();
                }),

            Action::make('retryFailedJobs')
                ->label('Retry Failed Jobs')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(function () {
                    try {
                        return DB::connection('central')->table('failed_jobs')->count() > 0;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->action(function () {
                    Artisan::call('queue:retry', ['all' => true]);

                    Notification::make()
                        ->success()
                        ->title('Failed Jobs Retried')
                        ->body('All failed jobs have been queued for retry.')
                        ->send();
                }),
        ];
    }
}
