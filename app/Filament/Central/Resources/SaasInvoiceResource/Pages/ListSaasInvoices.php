<?php

namespace App\Filament\Central\Resources\SaasInvoiceResource\Pages;

use App\Filament\Central\Resources\SaasInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaasInvoices extends ListRecords
{
    protected static string $resource = SaasInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
