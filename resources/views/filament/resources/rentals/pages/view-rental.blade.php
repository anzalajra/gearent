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
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <x-filament::badge :color="\App\Models\Rental::getStatusColor($rental->status)">
                    {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                </x-filament::badge>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                <p class="font-semibold">{{ $rental->customer->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                <p class="font-semibold">{{ $rental->customer->phone ?? '-' }}</p>
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
                <p class="text-sm text-gray-500 dark:text-gray-400">Returned Date</p>
                <p class="font-semibold">{{ $rental->returned_date ? $rental->returned_date->format('d M Y H:i') : '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                <p class="font-semibold">Rp {{ number_format($rental->total, 0, ',', '.') }}</p>
            </div>
            @if($rental->notes)
            <div class="col-span-full">
                <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                <p>{{ $rental->notes }}</p>
            </div>
            @endif
            @if($rental->status === 'cancelled' && $rental->cancel_reason)
            <div class="col-span-full">
                <p class="text-sm text-gray-500 dark:text-gray-400">Cancel Reason</p>
                <p class="text-danger-600">{{ $rental->cancel_reason }}</p>
            </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Rental Items
        </x-slot>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left p-3 font-medium">Product</th>
                    <th class="text-left p-3 font-medium">Serial Number</th>
                    <th class="text-left p-3 font-medium">Kits</th>
                    <th class="text-left p-3 font-medium">Days</th>
                    <th class="text-right p-3 font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rental->items as $item)
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="p-3">{{ $item->productUnit->product->name }}</td>
                    <td class="p-3">{{ $item->productUnit->serial_number }}</td>
                    <td class="p-3">{{ $item->rentalItemKits->count() }} kits</td>
                    <td class="p-3">{{ $item->days }}</td>
                    <td class="p-3 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>