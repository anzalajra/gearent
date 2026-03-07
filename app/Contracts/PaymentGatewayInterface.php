<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create a payment transaction.
     *
     * @param  string  $orderId  Unique order/merchant order ID (max 50 chars)
     * @param  int  $amount  Payment amount in IDR (no decimals)
     * @param  string  $paymentMethodCode  Payment channel code (e.g. "BC", "SP", "I1")
     * @param  array  $params  Additional params: email, customerVaName, productDetails, expiryPeriod, etc.
     * @return array{reference: string, vaNumber: ?string, qrString: ?string, paymentUrl: ?string, amount: int, statusCode: string}
     */
    public function createTransaction(string $orderId, int $amount, string $paymentMethodCode, array $params = []): array;

    /**
     * Check the status of an existing transaction.
     *
     * @return array{merchantOrderId: string, reference: string, amount: int, statusCode: string, statusMessage: string}
     */
    public function checkTransactionStatus(string $orderId): array;

    /**
     * Verify an incoming callback/webhook request.
     */
    public function verifyCallback(Request $request): bool;

    /**
     * Parse callback payload into a normalized structure.
     *
     * @return array{orderId: string, reference: string, amount: int, resultCode: string, paymentCode: string, raw: array}
     */
    public function parseCallback(Request $request): array;

    /**
     * Get available payment methods for a given amount.
     *
     * @return array<int, array{paymentMethod: string, paymentName: string, paymentImage: string, totalFee: string}>
     */
    public function getPaymentMethods(int $amount): array;
}
