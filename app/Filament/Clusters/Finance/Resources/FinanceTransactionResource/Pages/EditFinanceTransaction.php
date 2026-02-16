<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinanceTransaction extends EditRecord
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
