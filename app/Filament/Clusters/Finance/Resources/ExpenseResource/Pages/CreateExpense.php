<?php

namespace App\Filament\Clusters\Finance\Resources\ExpenseResource\Pages;

use App\Filament\Clusters\Finance\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['type'] = 'expense';
        return $data;
    }
}
