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
                        <td class="py-3 pr-6 font-semibold">{{ $rental->customer->name }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">End Date</td>
                        <td class="py-3 font-semibold">{{ $rental->end_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Phone</td>
                        <td class="py-3 pr-6 font-semibold">{{ $rental->customer->phone ?? '-' }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Returned Date</td>
                        <td class="py-3 font-semibold">{{ $rental->returned_date ? $rental->returned_date->format('d M Y H:i') : '-' }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Status</td>
                        <td class="py-3 pr-6">
                            <x-filament::badge :color="\App\Models\Rental::getStatusColor($rental->status)">
                                {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                            </x-filament::badge>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Total</td>
                        <td class="py-3 font-semibold">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
                    </tr>
                    @if($rental->notes)
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Notes</td>
                        <td class="py-3" colspan="3">{{ $rental->notes }}</td>
                    </tr>
                    @endif
                    @if($rental->status === 'cancelled' && $rental->cancel_reason)
                    <tr>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Cancel Reason</td>
                        <td class="py-3 text-danger-600" colspan="3">{{ $rental->cancel_reason }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Rental Items
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="text-left py-3 pr-4 font-semibold">Product</th>
                        <th class="text-left py-3 pr-4 font-semibold">Serial Number</th>
                        <th class="text-left py-3 pr-4 font-semibold">Kits</th>
                        <th class="text-left py-3 pr-4 font-semibold">Days</th>
                        <th class="text-right py-3 font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rental->items as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-3 pr-4">{{ $item->productUnit->product->name }}</td>
                        <td class="py-3 pr-4">{{ $item->productUnit->serial_number }}</td>
                        <td class="py-3 pr-4">{{ $item->rentalItemKits->count() }} kits</td>
                        <td class="py-3 pr-4">{{ $item->days }}</td>
                        <td class="py-3 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                        <td colspan="4" class="py-3 pr-4 text-right font-semibold">Total:</td>
                        <td class="py-3 text-right font-semibold">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    @if($rental->canBeEdited())
    <x-filament::section>
        <div class="flex justify-end">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Resources\Rentals\RentalResource::getUrl('edit', ['record' => $rental]) }}"
                color="gray"
                icon="heroicon-o-pencil-square"
            >
                Edit Rental
            </x-filament::button>
        </div>
    </x-filament::section>
    @endif
</x-filament-panels::page>