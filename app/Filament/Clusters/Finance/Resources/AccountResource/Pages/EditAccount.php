<?php

namespace App\Filament\Clusters\Finance\Resources\AccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\AccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save changes')
                ->action('save')
                ->keyBindings(['mod+s']),
            \Filament\Actions\Action::make('cancel')
                ->label('Cancel')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
            DeleteAction::make(),
        ];
    }
}
