<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Rental Information
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Rental Code</p>
                <p class="font-semibold">{{ $rental->rental_code }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                <p class="font-semibold">
                    <a href="{{ route('filament.admin.resources.customers.edit', $rental->customer_id) }}" class="text-primary-600 hover:underline">
                        {{ $rental->customer->name }}
                    </a>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                <p class="font-semibold">{{ $rental->customer->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <x-filament::badge :color="$rental->status === 'pending' ? 'warning' : 'danger'">
                    {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                </x-filament::badge>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Start Date</p>
                <p class="font-semibold">{{ $rental->start_date->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">End Date</p>
                <p class="font-semibold">{{ $rental->end_date->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Product Availability</p>
                @php
                    $availability = $this->getAvailabilityStatus();
                @endphp
                @if($availability['available'])
                    <x-filament::badge color="success">Available</x-filament::badge>
                @else
                    <x-filament::badge color="danger">Unavailable</x-filament::badge>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                <p class="font-semibold">{{ $rental->notes ?? '-' }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Items & Kits
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    <div class="flex justify-end mt-6">
        @foreach($this->getFooterActions() as $action)
            {{ $action }}
        @endforeach
    </div>
</x-filament-panels::page>