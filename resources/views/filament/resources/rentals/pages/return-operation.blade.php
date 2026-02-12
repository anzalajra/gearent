<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Rental Information
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 pr-6 font-medium text-gray-500" style="width: 15%;">Rental Code</td>
                        <td class="py-3 pr-6 font-semibold" style="width: 35%;">{{ $rental->rental_code }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500" style="width: 15%;">Start Date</td>
                        <td class="py-3 font-semibold" style="width: 35%;">{{ $rental->start_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 pr-6 font-medium text-gray-500">Customer</td>
                        <td class="py-3 pr-6 font-semibold">
                            <a href="{{ route('filament.admin.resources.customers.edit', $rental->user_id) }}" class="text-primary-600 hover:underline">
                                {{ $rental->customer->name }}
                            </a>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500">End Date</td>
                        <td class="py-3 font-semibold">{{ $rental->end_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 pr-6 font-medium text-gray-500">Phone</td>
                        <td class="py-3 pr-6 font-semibold">{{ $rental->customer->phone ?? '-' }}</td>
                        <td class="py-3 pr-6 font-medium text-gray-500">All Kits Checked</td>
                        <td class="py-3">
                            @if($this->canValidateReturn())
                                <x-filament::badge color="success">Yes, All Checked</x-filament::badge>
                            @else
                                <x-filament::badge color="warning">Pending Check</x-filament::badge>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6 font-medium text-gray-500">Status</td>
                        <td class="py-3 pr-6">
                            <x-filament::badge :color="$rental->status === 'active' ? 'success' : 'danger'">
                                {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                            </x-filament::badge>
                        </td>
                        <td class="py-3 pr-6 font-medium text-gray-500">Notes</td>
                        <td class="py-3 font-semibold">{{ $rental->notes ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Items & Kits Return Checklist
        </x-slot>

        @if(!$this->canValidateReturn())
            <div class="mb-4 p-3 bg-warning-50 rounded-lg border border-warning-200">
                <p class="text-sm text-warning-600">
                    ⚠️ Please check all kits as returned before validating the return.
                </p>
            </div>
        @endif

        {{ $this->table }}
    </x-filament::section>

    <x-filament::section>
        <div class="flex justify-end">
            {{ ($this->validateReturnAction)(['rental' => $this->rental]) }}
        </div>
    </x-filament::section>
</x-filament-panels::page>