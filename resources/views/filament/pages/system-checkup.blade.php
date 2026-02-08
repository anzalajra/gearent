<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statuses = [
                ['title' => 'Database', 'icon' => 'heroicon-o-server', 'data' => $dbStatus],
                ['title' => 'Storage', 'icon' => 'heroicon-o-folder', 'data' => $storageStatus],
                ['title' => 'Cache', 'icon' => 'heroicon-o-bolt', 'data' => $cacheStatus],
                ['title' => 'Symlink', 'icon' => 'heroicon-o-link', 'data' => $symlinkStatus],
                ['title' => 'Logs', 'icon' => 'heroicon-o-document-text', 'data' => $logStatus],
                ['title' => 'Queue', 'icon' => 'heroicon-o-queue-list', 'data' => $queueStatus],
            ];
        @endphp

        @foreach ($statuses as $status)
            <x-filament::section>
                <div class="flex items-center gap-4">
                    @php
                        $colorClass = match($status['data']['color']) {
                            'success' => 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400',
                            'danger' => 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400',
                            'warning' => 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400',
                            default => 'text-gray-600 bg-gray-50 dark:bg-gray-900/20 dark:text-gray-400',
                        };
                        $textClass = match($status['data']['color']) {
                            'success' => 'text-green-600 dark:text-green-400',
                            'danger' => 'text-red-600 dark:text-red-400',
                            'warning' => 'text-yellow-600 dark:text-yellow-400',
                            default => 'text-gray-600 dark:text-gray-400',
                        };
                    @endphp
                    <div class="p-3 rounded-full {{ $colorClass }}">
                        @svg($status['icon'], 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-950 dark:text-white">{{ $status['title'] }}</h3>
                        <p class="text-sm {{ $textClass }} font-bold">{{ $status['data']['message'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-filament::section>
            <x-slot name="heading">
                Resource Usage
            </x-slot>
             <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div class="sm:col-span-1 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Storage Used</dt>
                    <dd class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $storageUsage }}</dd>
                    <p class="text-xs text-gray-400 mt-1">Files in storage/app/public</p>
                </div>
                <div class="sm:col-span-1 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Size</dt>
                    <dd class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $databaseUsage }}</dd>
                    <p class="text-xs text-gray-400 mt-1">Data + Indexes</p>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Environment
            </x-slot>
             <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $phpVersion }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Laravel Version</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $laravelVersion }}</dd>
                </div>
                 <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Environment</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ app()->environment() }}</dd>
                </div>
                 <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Debug Mode</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ config('app.debug') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Maintenance Operations
        </x-slot>
        <x-slot name="description">
            Run these tools to fix common system issues.
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Cache Tools --}}
            <div class="space-y-3">
                <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    @svg('heroicon-o-bolt', 'w-5 h-5 text-gray-400')
                    Cache Management
                </h4>
                <div class="space-y-2">
                    {{ $this->optimizeClearAction }}
                    <p class="text-xs text-gray-500">Standard Laravel cache clearing.</p>
                </div>
                 <div class="space-y-2 pt-2 border-t dark:border-gray-700">
                    {{ $this->deepCleanCacheAction }}
                    <p class="text-xs text-gray-500">Emergency manual file deletion.</p>
                </div>
            </div>

            {{-- Storage & Logs --}}
            <div class="space-y-3">
                 <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    @svg('heroicon-o-folder', 'w-5 h-5 text-gray-400')
                    Storage & Logs
                </h4>
                <div class="space-y-2">
                    {{ $this->fixStorageLinkAction }}
                    <p class="text-xs text-gray-500">Fix broken image links.</p>
                </div>
                 <div class="space-y-2 pt-2 border-t dark:border-gray-700">
                    {{ $this->cleanLogsAction }}
                    <p class="text-xs text-gray-500">Truncate laravel.log file.</p>
                </div>
            </div>

             {{-- Database & Queue --}}
            <div class="space-y-3">
                 <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    @svg('heroicon-o-circle-stack', 'w-5 h-5 text-gray-400')
                    Database & Queue
                </h4>
                <div class="space-y-2">
                    <div class="flex gap-2">
                        {{ $this->runMigrationsAction }}
                        {{ $this->viewMigrationSqlAction }}
                    </div>
                    <p class="text-xs text-gray-500">Run pending database migrations.</p>
                </div>
                <div class="space-y-2 pt-2 border-t dark:border-gray-700">
                    {{ $this->retryFailedJobsAction }}
                    <p class="text-xs text-gray-500">Retry all failed background jobs.</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
