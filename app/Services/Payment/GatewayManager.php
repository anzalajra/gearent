<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use App\Services\Payment\Drivers\DuitkuDriver;
use InvalidArgumentException;

class GatewayManager
{
    protected static array $drivers = [
        'duitku' => DuitkuDriver::class,
    ];

    public static function resolve(string $gatewayCode): PaymentGatewayInterface
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)
            ->where('is_active', true)
            ->firstOrFail();

        $driverClass = self::$drivers[$gatewayCode]
            ?? throw new InvalidArgumentException("Unknown payment gateway: {$gatewayCode}");

        return new $driverClass($gateway);
    }

    public static function resolveById(int $gatewayId): PaymentGatewayInterface
    {
        $gateway = PaymentGateway::where('id', $gatewayId)
            ->where('is_active', true)
            ->firstOrFail();

        return self::resolve($gateway->code);
    }
}
