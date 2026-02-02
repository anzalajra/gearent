<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Rental Information
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400" style="width: 15%;">Rental Code</td>
                        <td class="py-3 pr-6 font-semibold" style="width: 35%;">{{ $rental->rental_code }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400" style="width: 15%;">Start Date</td>
                        <td class="py-3 font-semibold" style="width: 35%;">{{ $rental->start_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Customer</td>
                        <td class="py-3 pr-6 font-semibold">
                            <a href="{{ route('filament.admin.resources.customers.edit', $rental->customer_id) }}" class="text-primary-600 hover:underline">
                                {{ $rental->customer->name }}
                            </a>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">End Date</td>
                        <td class="py-3 font-semibold">{{ $rental->end_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Phone</td>
                        <td class="py-3 pr-6 font-semibold">{{ $rental->customer->phone ?? '-' }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Availability</td>
                        <td class="py-3">
                            @php $availability = $this->getAvailabilityStatus(); @endphp
                            @if($availability['available'])
                                <x-filament::badge color="success">Available</x-filament::badge>
                            @else
                                <x-filament::badge color="danger">Unavailable</x-filament::badge>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Status</td>
                        <td class="py-3 pr-6">
                            <x-filament::badge :color="in_array($rental->status, ['pending']) ? 'warning' : 'danger'">
                                {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                            </x-filament::badge>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Notes</td>
                        <td class="py-3 font-semibold">{{ $rental->notes ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Items & Kits
        </x-slot>

        @if(!$this->allItemsKitChecked())
            <div class="mb-4 p-3 bg-warning-50 dark:bg-warning-950 rounded-lg border border-warning-200 dark:border-warning-800">
                <p class="text-sm text-warning-600 dark:text-warning-400">
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