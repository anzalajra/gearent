<?php

namespace App\Filament\Clusters\Finance\Resources\BillResource\Pages;

use App\Filament\Clusters\Finance\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
