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
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada invoice.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Invoice #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Terbit</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Dibayar</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400"></th>
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
                                        $statusEnum = $invoice->status;
                                        $invoiceColor = $statusEnum instanceof \App\Enums\SaasInvoiceStatus
                                            ? $statusEnum->getColor()
                                            : 'gray';
                                        $invoiceLabel = $statusEnum instanceof \App\Enums\SaasInvoiceStatus
                                            ? $statusEnum->getLabel()
                                            : ucfirst((string) $statusEnum);
                                    @endphp
                                    <x-filament::badge :color="$invoiceColor">
                                        {{ $invoiceLabel }}
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
                                <td class="px-4 py-3">
                                    @if (! $invoice->isPaid())
                                        <x-filament::button
                                            size="sm"
                                            color="primary"
                                            wire:click="selectInvoice({{ $invoice->id }})"
                                        >
                                            Bayar
                                        </x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Payment Modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closePaymentModal">
            <div class="mx-4 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if ($paymentInstructions)
                            Instruksi Pembayaran
                        @else
                            Pilih Metode Pembayaran
                        @endif
                    </h3>
                    <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                @if (! $paymentInstructions)
                    {{-- Payment Method Selection --}}
                    <div class="space-y-3">
                        @foreach ($this->paymentMethods as $method)
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors
                                    {{ $selectedPaymentMethodId === $method->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700' }}"
                                wire:click="$set('selectedPaymentMethodId', {{ $method->id }})"
                            >
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $method->id }}"
                                    wire:model="selectedPaymentMethodId"
                                    class="text-primary-500"
                                >
                                <div class="flex-1">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $method->display_name }}</span>
                                    @if ((float) $method->admin_fee > 0)
                                        <span class="ml-2 text-xs text-gray-500">
                                            + {{ $method->admin_fee_type === 'percentage' ? $method->admin_fee . '%' : 'Rp ' . number_format((float) $method->admin_fee, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                @if ($method->icon)
                                    <img src="{{ $method->icon }}" alt="{{ $method->display_name }}" class="h-6">
                                @endif
                            </label>
                        @endforeach

                        @if ($this->paymentMethods->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Belum ada metode pembayaran yang tersedia. Hubungi administrator.
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <x-filament::button color="gray" wire:click="closePaymentModal">
                            Batal
                        </x-filament::button>
                        <x-filament::button
                            color="primary"
                            wire:click="initiatePayment"
                            :disabled="! $selectedPaymentMethodId"
                        >
                            Bayar Sekarang
                        </x-filament::button>
                    </div>
                @else
                    {{-- Payment Instructions --}}
                    <div class="space-y-4" wire:poll.5s="checkPaymentStatus">
                        @if (! empty($paymentInstructions['vaNumber']))
                            <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor Virtual Account</p>
                                <p class="mt-1 text-2xl font-bold tracking-wider text-gray-900 dark:text-white">
                                    {{ $paymentInstructions['vaNumber'] }}
                                </p>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Transfer sesuai nominal ke nomor VA di atas melalui ATM, Mobile Banking, atau Internet Banking.
                            </p>
                        @endif

                        @if (! empty($paymentInstructions['qrString']))
                            <div class="flex flex-col items-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                <p class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-400">Scan QR Code</p>
                                <div id="qrcode-container" class="rounded-lg bg-white p-3"></div>
                            </div>
                            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                                Scan QR Code di atas menggunakan aplikasi e-wallet atau mobile banking yang mendukung QRIS.
                            </p>
                            <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
                            <script>
                                document.addEventListener('livewire:navigated', function() {
                                    const container = document.getElementById('qrcode-container');
                                    if (container) {
                                        container.innerHTML = '';
                                        new QRCode(container, {
                                            text: @js($paymentInstructions['qrString']),
                                            width: 200,
                                            height: 200,
                                        });
                                    }
                                });
                                // Also run immediately
                                (function() {
                                    const container = document.getElementById('qrcode-container');
                                    if (container) {
                                        container.innerHTML = '';
                                        new QRCode(container, {
                                            text: @js($paymentInstructions['qrString']),
                                            width: 200,
                                            height: 200,
                                        });
                                    }
                                })();
                            </script>
                        @endif

                        @if (! empty($paymentInstructions['paymentUrl']))
                            <div class="text-center">
                                <a href="{{ $paymentInstructions['paymentUrl'] }}" target="_blank"
                                   class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    Buka Halaman Pembayaran
                                </a>
                            </div>
                        @endif

                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <x-heroicon-o-information-circle class="inline h-4 w-4" />
                                Halaman ini akan otomatis terupdate setelah pembayaran berhasil.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <x-filament::button color="gray" wire:click="closePaymentModal">
                                Tutup
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
