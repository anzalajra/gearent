<?php

namespace App\Filament\Pages;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SaasInvoice;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class SubscriptionBilling extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Subscription & Billing';

    protected string $view = 'filament.pages.subscription-billing';

    public ?string $planName = null;

    public ?string $planStatus = null;

    public ?string $statusColor = null;

    public ?string $expiresAt = null;

    public ?string $trialEndsAt = null;

    public array $usageStats = [];

    public $invoices = [];

    // Payment flow properties
    public ?int $selectedInvoiceId = null;

    public ?int $selectedPaymentMethodId = null;

    public ?array $paymentInstructions = null;

    public bool $showPaymentModal = false;

    public function mount(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            return;
        }

        $tenant->load('subscriptionPlan');

        $this->planName = $tenant->subscriptionPlan?->name ?? 'No Plan';
        $this->planStatus = ucfirst($tenant->status);
        $this->statusColor = match ($tenant->status) {
            'active' => 'success',
            'trial' => 'warning',
            'grace_period' => 'warning',
            'suspended' => 'danger',
            default => 'gray',
        };

        $this->expiresAt = $tenant->subscription_ends_at?->format('d M Y');
        $this->trialEndsAt = $tenant->trial_ends_at?->format('d M Y');

        $plan = $tenant->subscriptionPlan;

        $this->usageStats = [
            [
                'label' => 'Users',
                'used' => User::count(),
                'limit' => $plan?->max_users,
            ],
            [
                'label' => 'Products',
                'used' => Product::count(),
                'limit' => $plan?->max_products,
            ],
            [
                'label' => 'Rental Transactions (this month)',
                'used' => $tenant->current_rental_transactions_month ?? 0,
                'limit' => $plan?->max_rental_transactions_per_month,
            ],
            [
                'label' => 'Storage',
                'used' => null,
                'limit' => $plan?->max_storage_mb,
                'suffix' => 'MB',
            ],
        ];

        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        $this->invoices = SaasInvoice::where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function paymentMethods(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentMethod::active()
            ->whereHas('paymentGateway', fn ($q) => $q->where('is_active', true))
            ->with('paymentGateway')
            ->orderBy('sort_order')
            ->get();
    }

    public function selectInvoice(int $invoiceId): void
    {
        $this->selectedInvoiceId = $invoiceId;
        $this->selectedPaymentMethodId = null;
        $this->paymentInstructions = null;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->selectedInvoiceId = null;
        $this->selectedPaymentMethodId = null;
        $this->paymentInstructions = null;
    }

    public function initiatePayment(): void
    {
        if (! $this->selectedInvoiceId || ! $this->selectedPaymentMethodId) {
            Notification::make()
                ->title('Pilih metode pembayaran terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $invoice = SaasInvoice::find($this->selectedInvoiceId);
        $method = PaymentMethod::find($this->selectedPaymentMethodId);

        if (! $invoice || ! $method) {
            Notification::make()
                ->title('Invoice atau metode pembayaran tidak ditemukan.')
                ->danger()
                ->send();

            return;
        }

        if ($invoice->isPaid()) {
            Notification::make()
                ->title('Invoice ini sudah dibayar.')
                ->info()
                ->send();
            $this->closePaymentModal();

            return;
        }

        try {
            $result = app(PaymentService::class)->createPayment($invoice, $method);
            $this->paymentInstructions = $result;
            $this->loadInvoices();

            Notification::make()
                ->title('Pembayaran berhasil dibuat. Silakan ikuti instruksi di bawah.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal membuat pembayaran: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkPaymentStatus(): void
    {
        if (! $this->selectedInvoiceId) {
            return;
        }

        $invoice = SaasInvoice::find($this->selectedInvoiceId);
        if (! $invoice) {
            return;
        }

        $invoice->refresh();

        if ($invoice->isPaid()) {
            Notification::make()
                ->title('Pembayaran berhasil! Subscription Anda telah diaktifkan.')
                ->success()
                ->send();

            $this->closePaymentModal();
            $this->loadInvoices();
            $this->mount();

            return;
        }

        // Try polling gateway status
        if ($invoice->gateway_reference_id && $invoice->payment_gateway_id) {
            try {
                $status = app(PaymentService::class)->checkPaymentStatus($invoice);

                if (($status['statusCode'] ?? '') === '00') {
                    $this->closePaymentModal();
                    $this->loadInvoices();
                    $this->mount();

                    return;
                }
            } catch (\Throwable $e) {
                // Silent fail on status check
            }
        }
    }
}
