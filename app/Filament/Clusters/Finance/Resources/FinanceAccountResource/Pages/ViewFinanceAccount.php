<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFinanceAccount extends ViewRecord
{
    /**
     * @var class-string<\App\Filament\Clusters\Finance\Resources\FinanceAccountResource>
     */
    protected static string $resource = 'App\Filament\Clusters\Finance\Resources\FinanceAccountResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
