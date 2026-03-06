<x-filament-panels::page>
    <div class="space-y-6">
        {{-- System Info + Health Checks --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">System Information</x-slot>

                <div class="space-y-3">
                    @foreach($systemInfo as $key => $value)
                        <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $key }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Health Checks</x-slot>

                <div class="space-y-3">
                    @foreach($healthChecks as $name => $check)
                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $name }}</span>
                            <span @class([
                                'flex items-center gap-1 text-sm font-medium',
                                'text-green-600 dark:text-green-400' => $check['color'] === 'success',
                                'text-red-600 dark:text-red-400' => $check['color'] === 'danger',
                                'text-yellow-600 dark:text-yellow-400' => $check['color'] === 'warning',
                            ])>
                                <x-dynamic-component :component="$check['icon']" class="w-5 h-5" />
                                {{ $check['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        {{-- Application Config Form --}}
        {{ $this->form }}
    </div>
</x-filament-panels::page>
