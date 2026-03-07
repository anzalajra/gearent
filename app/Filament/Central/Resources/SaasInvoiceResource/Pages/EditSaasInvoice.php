<?php

namespace App\Filament\Central\Resources\SaasInvoiceResource\Pages;

use App\Filament\Central\Resources\SaasInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaasInvoice extends EditRecord
{
    protected static string $resource = SaasInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
