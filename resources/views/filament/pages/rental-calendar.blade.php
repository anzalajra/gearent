<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-4">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #f59e0b;"></div>
                <span class="text-sm">Pending</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #10b981;"></div>
                <span class="text-sm">Active</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background: #3b82f6;"></div>
                <span class="text-sm">Completed</span>
            </div>
        </div>

        <a href="{{ url('/admin/rentals/create') }}" class="filament-button filament-button-size-md">
            New Rental
        </a>
    </div>

    {{-- Widget dirender via getHeaderWidgets() di Page, jadi jangan panggil @livewire() di sini --}}
</x-filament-panels::page>