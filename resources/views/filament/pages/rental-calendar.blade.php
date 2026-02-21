<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #f97316;"></div>
            <span class="text-sm">Quotation</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #3b82f6;"></div>
            <span class="text-sm">Confirmed</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #22c55e;"></div>
            <span class="text-sm">Active</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #a855f7;"></div>
            <span class="text-sm">Completed</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #6b7280;"></div>
            <span class="text-sm">Cancelled</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background: #ef4444;"></div>
            <span class="text-sm">Late Pickup/Return</span>
        </div>
        </div>

        <a href="{{ url('/admin/rentals/create') }}" class="filament-button filament-button-size-md">
            New Rental
        </a>
    </div>

    {{-- Widget dirender via getHeaderWidgets() di Page, jadi jangan panggil @livewire() di sini --}}
    
    <x-filament-actions::modals />
</x-filament-panels::page>