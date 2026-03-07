<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use Duitku\Api as DuitkuApi;
use Duitku\Config as DuitkuConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DuitkuDriver implements PaymentGatewayInterface
{
    protected DuitkuConfig $config;

    protected string $merchantCode;

    protected string $apiKey;

    public function __construct(PaymentGateway $gateway)
    {
        $creds = $gateway->credentials;
        $this->merchantCode = $creds['merchant_code'] ?? '';
        $this->apiKey = $creds['api_key'] ?? '';

        $this->config = new DuitkuConfig($this->apiKey, $this->merchantCode);
        $this->config->setSandboxMode($gateway->is_sandbox);
        $this->config->setSanitizedMode(true);
        $this->config->setDuitkuLogs(false);
    }

    public function createTransaction(string $orderId, int $amount, string $paymentMethodCode, array $params = []): array
    {
        $callbackUrl = $params['callbackUrl'] ?? route('payment.callback', ['gateway' => 'duitku']);
        $returnUrl = $params['returnUrl'] ?? route('payment.return');

        $requestParams = [
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethodCode,
            'merchantOrderId' => $orderId,
            'productDetails' => $params['productDetails'] ?? 'Langganan Zewalo',
            'email' => $params['email'] ?? 'billing@zewalo.com',
            'customerVaName' => mb_substr($params['customerVaName'] ?? 'Zewalo', 0, 20),
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'expiryPeriod' => $params['expiryPeriod'] ?? 1440, // 24 hours default
        ];

        if (! empty($params['phoneNumber'])) {
            $requestParams['phoneNumber'] = $params['phoneNumber'];
        }

        if (! empty($params['itemDetails'])) {
            $requestParams['itemDetails'] = $params['itemDetails'];
        }

        if (! empty($params['customerDetail'])) {
            $requestParams['customerDetail'] = $params['customerDetail'];
        }

        try {
            $response = DuitkuApi::createInvoice($requestParams, $this->config);
            $result = json_decode($response, true);

            if (! $result || ($result['statusCode'] ?? '') !== '00') {
                Log::error('Duitku createTransaction failed', [
                    'orderId' => $orderId,
                    'response' => $result,
                ]);
                throw new RuntimeException('Duitku payment creation failed: '.($result['statusMessage'] ?? 'Unknown error'));
            }

            return [
                'reference' => $result['reference'] ?? '',
                'vaNumber' => $result['vaNumber'] ?? null,
                'qrString' => $result['qrString'] ?? null,
                'paymentUrl' => $result['paymentUrl'] ?? null,
                'amount' => (int) ($result['amount'] ?? $amount),
                'statusCode' => $result['statusCode'] ?? '',
                'raw' => $result,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Duitku createTransaction exception', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Duitku payment creation failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function checkTransactionStatus(string $orderId): array
    {
        try {
            $response = DuitkuApi::transactionStatus($orderId, $this->config);
            $result = json_decode($response, true);

            return [
                'merchantOrderId' => $result['merchantOrderId'] ?? $orderId,
                'reference' => $result['reference'] ?? '',
                'amount' => (int) ($result['amount'] ?? 0),
                'statusCode' => $result['statusCode'] ?? '',
                'statusMessage' => $result['statusMessage'] ?? '',
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('Duitku checkTransactionStatus exception', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Duitku status check failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function verifyCallback(Request $request): bool
    {
        $merchantCode = $request->input('merchantCode', '');
        $amount = $request->input('amount', '');
        $merchantOrderId = $request->input('merchantOrderId', '');
        $receivedSignature = $request->input('signature', '');

        $calculatedSignature = md5($merchantCode.$amount.$merchantOrderId.$this->apiKey);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    public function parseCallback(Request $request): array
    {
        return [
            'orderId' => $request->input('merchantOrderId', ''),
            'reference' => $request->input('reference', ''),
            'amount' => (int) $request->input('amount', 0),
            'resultCode' => $request->input('resultCode', ''),
            'paymentCode' => $request->input('paymentCode', ''),
            'settlementDate' => $request->input('settlementDate', ''),
            'publisherOrderId' => $request->input('publisherOrderId', ''),
            'raw' => $request->all(),
        ];
    }

    public function getPaymentMethods(int $amount): array
    {
        try {
            $response = DuitkuApi::getPaymentMethod($amount, $this->config);
            $result = json_decode($response, true);

            if (($result['responseCode'] ?? '') !== '00') {
                return [];
            }

            return $result['paymentFee'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Duitku getPaymentMethods exception', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
