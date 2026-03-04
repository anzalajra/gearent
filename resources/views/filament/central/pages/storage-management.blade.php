<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Storage Overview --}}
        <x-filament::section>
            <x-slot name="heading">Storage Overview</x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getFormattedStorageInfo() as $label => $value)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $value }}</dd>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Storage Paths --}}
        <x-filament::section>
            <x-slot name="heading">Storage Paths</x-slot>
            
            <dl class="grid grid-cols-1 gap-4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage Path</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $storageInfo['storage_path'] ?? 'N/A' }}</dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Public Storage Path</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $storageInfo['public_path'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
