<?php

namespace App\Filament\Clusters\Finance\Resources\BillResource\Pages;

use App\Filament\Clusters\Finance\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }
}
