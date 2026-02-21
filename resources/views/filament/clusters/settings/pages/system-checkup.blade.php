<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                System Information
            </x-slot>

            <div class="space-y-4">
                @foreach($systemInfo as $key => $value)
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-medium text-gray-600 dark:text-gray-400">{{ $key }}</span>
                        <span class="text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Health Checks
            </x-slot>

            <div class="space-y-4">
                {{-- Database --}}
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="font-medium text-gray-600 dark:text-gray-400">Database Connection</span>
                    @if($checks['database']['status'] === 'ok')
                        <span class="text-success-600 flex items-center gap-1">
                            <x-heroicon-o-check-circle class="w-5 h-5" /> Connected
                        </span>
                    @else
                        <span class="text-danger-600 flex items-center gap-1">
                            <x-heroicon-o-x-circle class="w-5 h-5" /> Error
                        </span>
                    @endif
                </div>

                {{-- Storage --}}
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="font-medium text-gray-600 dark:text-gray-400">Storage Permissions</span>
                    @if($checks['storage']['status'] === 'ok')
                        <span class="text-success-600 flex items-center gap-1">
                            <x-heroicon-o-check-circle class="w-5 h-5" /> Writable
                        </span>
                    @else
                        <span class="text-danger-600 flex items-center gap-1">
                            <x-heroicon-o-x-circle class="w-5 h-5" /> Error
                        </span>
                    @endif
                </div>

                {{-- Storage Link --}}
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="font-medium text-gray-600 dark:text-gray-400">Storage Symlink</span>
                    @if($checks['storage_link']['status'] === 'ok')
                        <span class="text-success-600 flex items-center gap-1">
                            <x-heroicon-o-check-circle class="w-5 h-5" /> Exists
                        </span>
                    @else
                        <span class="text-warning-600 flex items-center gap-1">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5" /> Missing
                        </span>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Quick Actions
        </x-slot>
        <x-slot name="description">
            Common maintenance tasks to fix issues.
        </x-slot>

        <div class="flex flex-wrap gap-4">
            <x-filament::button 
                wire:click="clearCache" 
                color="warning"
                icon="heroicon-o-trash">
                Clear System Cache
            </x-filament::button>

            @if($checks['storage_link']['status'] !== 'ok')
                <x-filament::button 
                    wire:click="createStorageLink" 
                    color="primary"
                    icon="heroicon-o-link">
                    Fix Storage Link
                </x-filament::button>
            @endif

            <x-filament::button 
                wire:click="migrateDatabase" 
                color="gray"
                icon="heroicon-o-circle-stack">
                Force Migrate Database
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
