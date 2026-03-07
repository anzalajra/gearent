<?php

namespace App\Filament\Central\Resources\PaymentGatewayResource\Pages;

use App\Filament\Central\Resources\PaymentGatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentGateways extends ListRecords
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
