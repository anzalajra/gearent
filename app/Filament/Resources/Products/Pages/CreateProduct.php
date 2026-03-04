<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\Tenancy\RentalLimitService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Saat limit paket Free habis, blokir penambahan produk baru
        RentalLimitService::ensureRentalLimitNotExceeded();

        return $data;
    }
}
