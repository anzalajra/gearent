<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Rental Information
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">Rental Code</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $rental->rental_code }}</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">Start Date</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $rental->start_date->format('d M Y H:i') }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">Customer</span>
                <a href="{{ route('filament.admin.resources.customers.edit', $rental->customer_id) }}" class="font-semibold text-primary-600 hover:underline">
                    {{ $rental->customer->name }}
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">End Date</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $rental->end_date->format('d M Y H:i') }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">Phone</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $rental->customer->phone ?? '-' }}</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="font-medium text-gray-500 dark:text-gray-400">Availability</span>
                <div>
                    @php $availability = $this->getAvailabilityStatus(); @endphp
                    @if($availability['available'])
                        <x-filament::badge color="success">Available</x-filament::badge>
                    @elseif(!empty($availability['unavailable_units']))
                        <x-filament::badge color="danger">Problem</x-filament::badge>
                    @else
                        <x-filament::badge color="danger">Unavailable</x-filament::badge>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2 md:border-b-0">
                <span class="font-medium text-gray-500 dark:text-gray-400">Status</span>
                <div>
                    <x-filament::badge :color="in_array($rental->status, ['pending']) ? 'warning' : 'danger'">
                        {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                    </x-filament::badge>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-2 md:border-b-0">
                <span class="font-medium text-gray-500 dark:text-gray-400">Notes</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $rental->notes ?? '-' }}</span>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Items & Kits
        </x-slot>

        @if(!$this->allItemsChecked())
            <div class="mb-4 p-3 bg-warning-50 rounded-lg border border-warning-200">
                <p class="text-sm text-warning-600">
                    ⚠️ Please check all kits before validating pickup.
                </p>
            </div>
        @endif

        {{ $this->table }}
    </x-filament::section>

    <x-filament::section>
        <div class="flex justify-end">
            {{ ($this->validatePickupAction)(['rental' => $this->rental]) }}
        </div>
    </x-filament::section>
</x-filament-panels::page>