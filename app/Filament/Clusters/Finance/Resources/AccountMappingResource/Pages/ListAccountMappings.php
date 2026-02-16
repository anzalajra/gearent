<?php

namespace App\Filament\Clusters\Finance\Resources\AccountMappingResource\Pages;

use App\Filament\Clusters\Finance\Resources\AccountMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountMappings extends ListRecords
{
    protected static string $resource = AccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
