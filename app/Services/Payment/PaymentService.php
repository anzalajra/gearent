<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\SaasInvoiceStatus;
use App\Models\PaymentMethod;
use App\Models\SaasInvoice;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentService
{
    /**
     * Create a payment for a SaaS invoice.
     *
     * @return array Payment instructions (reference, vaNumber, qrString, paymentUrl, etc.)
     */
    public function createPayment(SaasInvoice $invoice, PaymentMethod $method): array
    {
        if ($invoice->isPaid()) {
            throw new RuntimeException('Invoice sudah dibayar.');
        }

        $gateway = GatewayManager::resolveById($method->payment_gateway_id);
        $orderId = $this->generateOrderId($invoice);
        $amount = (int) ceil((float) $invoice->total + $method->calculateAdminFee((float) $invoice->total));

        $tenant = $invoice->tenant;

        $result = $gateway->createTransaction($orderId, $amount, $method->channel_code ?? $method->type->value, [
            'productDetails' => "Langganan Zewalo - {$invoice->invoice_number}",
            'email' => $tenant->email ?? 'billing@zewalo.com',
            'customerVaName' => mb_substr($tenant->name ?? 'Tenant', 0, 20),
            'expiryPeriod' => 1440, // 24 hours
            'itemDetails' => [
                [
                    'name' => "Langganan Zewalo - {$invoice->invoice_number}",
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
        ]);

        // Store payment data on the invoice
        $invoice->update([
            'payment_gateway_id' => $method->payment_gateway_id,
            'payment_method_id' => $method->id,
            'payment_data' => $result,
            'gateway_reference_id' => $orderId,
        ]);

        Log::info('Payment created for SaaS invoice', [
            'invoice' => $invoice->invoice_number,
            'orderId' => $orderId,
            'method' => $method->display_name,
            'amount' => $amount,
        ]);

        return $result;
    }

    /**
     * Handle successful payment (called from webhook/callback).
     */
    public function handlePaymentSuccess(SaasInvoice $invoice): void
    {
        if ($invoice->isPaid()) {
            return;
        }

        $invoice->update([
            'status' => SaasInvoiceStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->activateSubscription($invoice);

        Log::info('Payment success for SaaS invoice', [
            'invoice' => $invoice->invoice_number,
            'tenant' => $invoice->tenant_id,
        ]);
    }

    /**
     * Check payment status via gateway API (manual polling).
     */
    public function checkPaymentStatus(SaasInvoice $invoice): array
    {
        if (! $invoice->gateway_reference_id || ! $invoice->payment_gateway_id) {
            throw new RuntimeException('Invoice tidak memiliki referensi payment gateway.');
        }

        $gateway = GatewayManager::resolveById($invoice->payment_gateway_id);
        $status = $gateway->checkTransactionStatus($invoice->gateway_reference_id);

        // Auto-handle if paid
        if (($status['statusCode'] ?? '') === '00' && ! $invoice->isPaid()) {
            $this->handlePaymentSuccess($invoice);
        }

        return $status;
    }

    protected function activateSubscription(SaasInvoice $invoice): void
    {
        $tenant = $invoice->tenant;
        $subscription = $invoice->tenantSubscription;

        if ($subscription) {
            $subscription->update(['status' => 'active']);

            $tenant->update([
                'status' => 'active',
                'subscription_ends_at' => $subscription->ends_at,
                'grace_period_ends_at' => null,
            ]);
        } else {
            // Fallback: just reactivate tenant
            $tenant->update([
                'status' => 'active',
                'grace_period_ends_at' => null,
            ]);
        }

        Log::info('Subscription activated via payment', [
            'tenant' => $tenant->id,
            'subscription' => $subscription?->id,
        ]);
    }

    protected function generateOrderId(SaasInvoice $invoice): string
    {
        // Max 50 chars for Duitku merchantOrderId
        $base = 'SAAS-'.$invoice->invoice_number;

        return mb_substr($base, 0, 40).'-'.time();
    }
}
