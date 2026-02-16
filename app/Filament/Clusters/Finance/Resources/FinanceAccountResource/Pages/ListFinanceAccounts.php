<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinanceAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceAccounts extends ListRecords
{
    protected static string $resource = FinanceAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
