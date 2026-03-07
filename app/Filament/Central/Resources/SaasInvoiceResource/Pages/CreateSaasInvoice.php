<?php

namespace App\Filament\Central\Resources\SaasInvoiceResource\Pages;

use App\Filament\Central\Resources\SaasInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSaasInvoice extends CreateRecord
{
    protected static string $resource = SaasInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
