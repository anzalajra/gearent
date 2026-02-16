<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinanceAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinanceAccount extends EditRecord
{
    protected static string $resource = FinanceAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
