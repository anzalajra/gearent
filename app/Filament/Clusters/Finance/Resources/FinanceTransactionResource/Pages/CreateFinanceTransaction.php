<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinanceTransaction extends CreateRecord
{
    protected static string $resource = FinanceTransactionResource::class;
}
