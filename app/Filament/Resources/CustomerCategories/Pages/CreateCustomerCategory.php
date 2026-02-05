<?php

namespace App\Filament\Resources\CustomerCategories\Pages;

use App\Filament\Resources\CustomerCategories\CustomerCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerCategory extends CreateRecord
{
    protected static string $resource = CustomerCategoryResource::class;
}
