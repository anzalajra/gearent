<?php

namespace App\Filament\Resources\ProductUnits\Pages;

use App\Filament\Resources\ProductUnits\ProductUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductUnit extends EditRecord
{
    protected static string $resource = ProductUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
