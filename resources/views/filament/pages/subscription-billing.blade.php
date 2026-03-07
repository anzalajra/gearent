<x-filament-panels::page>
    {{-- Plan Status Card --}}
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $planName }}
                </h3>
                <div class="mt-1 flex items-center gap-3">
                    <x-filament::badge :color="$statusColor">
                        {{ $planStatus }}
                    </x-filament::badge>

                    @if ($expiresAt)
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Valid until: {{ $expiresAt }}
                        </span>
                    @endif

                    @if ($trialEndsAt && $planStatus === 'Trial')
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Trial ends: {{ $trialEndsAt }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Usage Statistics --}}
    <x-filament::section heading="Usage">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($usageStats as $stat)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $stat['label'] }}
                    </p>
                    <div class="mt-2">
                        @if ($stat['limit'] !== null)
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $stat['used'] ?? '—' }}
                                <span class="text-sm font-normal text-gray-400">/ {{ $stat['limit'] }}{{ $stat['suffix'] ?? '' }}</span>
                            </p>
                            @if ($stat['used'] !== null)
                                @php
                                    $percentage = $stat['limit'] > 0 ? min(100, round(($stat['used'] / $stat['limit']) * 100)) : 0;
                                    $barColor = $percentage >= 90 ? 'bg-danger-500' : ($percentage >= 70 ? 'bg-warning-500' : 'bg-primary-500');
                                @endphp
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                </div>
                            @endif
                        @else
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $stat['used'] ?? '—' }}
                                <span class="text-sm font-normal text-gray-400">/ Unlimited</span>
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Invoice History --}}
    <x-filament::section heading="Invoice History">
        @if ($invoices->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No invoices yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Invoice #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Issued</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    Rp {{ number_format($invoice->total, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $invoiceColor = match($invoice->status) {
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'overdue' => 'danger',
                                            'cancelled' => 'gray',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$invoiceColor">
                                        {{ ucfirst($invoice->status) }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $invoice->issued_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $invoice->due_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $invoice->paid_at?->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
