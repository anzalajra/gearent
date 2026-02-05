<?php

namespace App\Filament\Resources\CustomerCategories\Pages;

use App\Filament\Resources\CustomerCategories\CustomerCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerCategory extends EditRecord
{
    protected static string $resource = CustomerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
