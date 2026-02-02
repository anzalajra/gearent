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
                <x-filament::badge :color="$record->status === 'pending' ? 'warning' : 'danger'">
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
                <p class="text-sm text-gray-500 dark:text-gray-400">Product Availability</p>
                @php
                    $availability = $this->getAvailabilityStatus();
                @endphp
                @if($availability['available'])
                    <x-filament::badge color="success">Available</x-filament::badge>
                @else
                    <x-filament::modal width="xl">
                        <x-slot name="trigger">
                            <x-filament::badge color="danger" class="cursor-pointer">
                                Unavailable
                            </x-filament::badge>
                        </x-slot>

                        <x-slot name="heading">
                            Conflicting Rentals
                        </x-slot>

                        <div class="space-y-4">
                            @foreach($availability['conflicts'] as $conflict)
                                <div class="p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                                    <p class="font-semibold">{{ $conflict['item']->productUnit->product->name }} - {{ $conflict['item']->productUnit->serial_number }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Conflicts with:</p>
                                    <ul class="list-disc list-inside text-sm mt-1">
                                        @foreach($conflict['conflicting_rentals'] as $conflictingRental)
                                            <li>{{ $conflictingRental->rental_code }} ({{ $conflictingRental->start_date->format('d M Y') }} - {{ $conflictingRental->end_date->format('d M Y') }})</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::modal>
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
            Items & Kits
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    {{-- Footer Validate Button --}}
    <div class="flex justify-end mt-6">
        @foreach($this->getFooterActions() as $action)
            {{ $action }}
        @endforeach
    </div>
</x-filament-panels::page>