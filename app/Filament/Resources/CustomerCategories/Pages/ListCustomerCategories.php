<?php

namespace App\Filament\Resources\CustomerCategories\Pages;

use App\Filament\Resources\CustomerCategories\CustomerCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerCategories extends ListRecords
{
    protected static string $resource = CustomerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
