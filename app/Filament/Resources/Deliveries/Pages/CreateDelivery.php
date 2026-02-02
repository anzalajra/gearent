<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function afterCreate(): void
    {
        $delivery = $this->record;
        $rental = $delivery->rental;

        // Auto-create delivery items from rental items
        foreach ($rental->items as $rentalItem) {
            // Create item for the product unit
            $delivery->items()->create([
                'rental_item_id' => $rentalItem->getKey(),
                'rental_item_kit_id' => null,
                'is_checked' => false,
            ]);

            // Create items for each kit
            foreach ($rentalItem->rentalItemKits as $kit) {
                $delivery->items()->create([
                    'rental_item_id' => $rentalItem->getKey(),
                    'rental_item_kit_id' => $kit->getKey(),
                    'is_checked' => false,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return DeliveryResource::getUrl('process', ['record' => $this->record]);
    }
}