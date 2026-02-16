<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceTransactions extends ListRecords
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
