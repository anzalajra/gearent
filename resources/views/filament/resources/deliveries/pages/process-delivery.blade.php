<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Delivery Information
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400" style="width: 15%;">Delivery Number</td>
                        <td class="py-3 pr-6 font-semibold" style="width: 35%;">{{ $delivery->delivery_number }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400" style="width: 15%;">Rental Code</td>
                        <td class="py-3 font-semibold" style="width: 35%;">{{ $delivery->rental->rental_code }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Type</td>
                        <td class="py-3 pr-6">
                            <x-filament::badge :color="$delivery->type === 'out' ? 'warning' : 'success'">
                                {{ $delivery->type === 'out' ? 'Keluar (Check-out)' : 'Masuk (Check-in)' }}
                            </x-filament::badge>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Customer</td>
                        <td class="py-3 font-semibold">{{ $delivery->rental->customer->name }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Date</td>
                        <td class="py-3 pr-6 font-semibold">{{ $delivery->date->format('d M Y') }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Phone</td>
                        <td class="py-3 font-semibold">{{ $delivery->rental->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Status</td>
                        <td class="py-3 pr-6">
                            <x-filament::badge :color="\App\Models\Delivery::getStatusColor($delivery->status)">
                                {{ ucfirst($delivery->status) }}
                            </x-filament::badge>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500 dark:text-gray-400">Checked By</td>
                        <td class="py-3 font-semibold">{{ $delivery->checkedBy?->name ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Items Checklist
        </x-slot>

        @if(!$this->allItemsChecked() && $delivery->status === 'draft')
            <div class="mb-4 p-3 bg-warning-50 dark:bg-warning-950 rounded-lg border border-warning-200 dark:border-warning-800">
                <p class="text-sm text-warning-600 dark:text-warning-400">
                    ⚠️ Please check all items before completing the delivery.
                </p>
            </div>
        @endif

        {{ $this->table }}
    </x-filament::section>

    @if($delivery->status === 'draft')
    <x-filament::section>
        <div class="flex justify-end">
            {{ ($this->completeAction)(['delivery' => $this->delivery]) }}
        </div>
    </x-filament::section>
    @endif
</x-filament-panels::page>