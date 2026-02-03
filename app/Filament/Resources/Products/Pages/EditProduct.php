<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Rental;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scheduled_rental')
                ->label('Scheduled Rental')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(fn ($record) => ProductResource::getUrl('schedule', ['record' => $record])),
            DeleteAction::make(),
        ];
    }
}
