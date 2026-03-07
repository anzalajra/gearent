<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Services\Payment\GatewayManager;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle payment callback from gateway (e.g. Duitku).
     * Duitku sends POST form-urlencoded with: merchantCode, amount, merchantOrderId, resultCode, reference, signature
     */
    public function callback(Request $request, string $gateway): JsonResponse
    {
        Log::info("Payment callback received for gateway: {$gateway}", [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $driver = GatewayManager::resolve($gateway);

            if (! $driver->verifyCallback($request)) {
                Log::warning("Payment callback verification failed for {$gateway}", [
                    'payload' => $request->all(),
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $parsed = $driver->parseCallback($request);

            $invoice = SaasInvoice::where('gateway_reference_id', $parsed['orderId'])->first();

            if (! $invoice) {
                Log::warning("No invoice found for order: {$parsed['orderId']}");

                return response()->json(['status' => 'ignored'], 200);
            }

            if ($invoice->isPaid()) {
                return response()->json(['status' => 'already_paid'], 200);
            }

            // resultCode "00" = Success, "01" = Failed
            if ($parsed['resultCode'] === '00') {
                app(PaymentService::class)->handlePaymentSuccess($invoice);
                Log::info("Invoice {$invoice->invoice_number} marked as paid via callback");
            } else {
                Log::info("Payment callback with non-success code for {$invoice->invoice_number}", [
                    'resultCode' => $parsed['resultCode'],
                ]);
            }

            return response()->json(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            Log::error("Payment callback processing failed: {$e->getMessage()}", [
                'exception' => $e,
                'gateway' => $gateway,
            ]);

            // Always return 200 to prevent Duitku from retrying
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Handle return URL redirect from payment gateway.
     * Duitku redirects with GET: ?merchantOrderId=X&resultCode=X&reference=X
     */
    public function returnUrl(Request $request): RedirectResponse
    {
        $orderId = $request->query('merchantOrderId', '');
        $resultCode = $request->query('resultCode', '');

        $message = match ($resultCode) {
            '00' => 'Pembayaran berhasil! Terima kasih.',
            '01' => 'Pembayaran sedang diproses. Mohon tunggu konfirmasi.',
            default => 'Pembayaran dibatalkan atau gagal.',
        };

        $type = $resultCode === '00' ? 'success' : ($resultCode === '01' ? 'info' : 'warning');

        // Try to find the tenant domain from the invoice
        $invoice = SaasInvoice::where('gateway_reference_id', $orderId)->first();
        if ($invoice) {
            $tenant = $invoice->tenant;
            $domain = $tenant->domains()->first();
            if ($domain) {
                $scheme = request()->getScheme();
                $port = request()->getPort();
                $portSuffix = (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80))
                    ? ":{$port}" : '';

                return redirect("{$scheme}://{$domain->domain}{$portSuffix}/admin/subscription-billing")
                    ->with($type, $message);
            }
        }

        // Fallback: redirect to central domain
        return redirect('/')->with($type, $message);
    }
}
