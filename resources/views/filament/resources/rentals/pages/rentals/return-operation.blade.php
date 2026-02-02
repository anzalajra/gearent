<x-filament-panels::page>
    {{-- Rental Info Section --}}
    <x-filament::section>
        <x-slot name="heading">
            Rental Information
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Rental Code</p>
                <p class="font-semibold">{{ $record->rental_code }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                <p class="font-semibold">
                    <a href="{{ route('filament.admin.resources.customers.edit', $record->customer_id) }}" class="text-primary-600 hover:underline">
                        {{ $record->customer->name }}
                    </a>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                <p class="font-semibold">{{ $record->customer->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <x-filament::badge :color="$record->status === 'active' ? 'success' : 'danger'">
                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                </x-filament::badge>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Start Date</p>
                <p class="font-semibold">{{ $record->start_date->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">End Date</p>
                <p class="font-semibold">{{ $record->end_date->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">All Kits Returned</p>
                @if($this->canValidateReturn())
                    <x-filament::badge color="success">Yes, All Returned</x-filament::badge>
                @else
                    <x-filament::badge color="warning">Pending Return</x-filament::badge>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                <p class="font-semibold">{{ $record->notes ?? '-' }}</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Items & Kits Table --}}
    <x-filament::section>
        <x-slot name="heading">
            Items & Kits Return Checklist
        </x-slot>

        @if(!$this->canValidateReturn())
            <x-filament::section class="mb-4 bg-warning-50 dark:bg-warning-900/20">
                <p class="text-warning-700 dark:text-warning-400">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 inline-block mr-1" />
                    Please check all kits as returned before validating the return.
                </p>
            </x-filament::section>
        @endif

        {{ $this->table }}
    </x-filament::section>

    {{-- Footer Validate Button --}}
    <div class="flex justify-end mt-6">
        @foreach($this->getFooterActions() as $action)
            {{ $action }}
        @endforeach
    </div>
</x-filament-panels::page>