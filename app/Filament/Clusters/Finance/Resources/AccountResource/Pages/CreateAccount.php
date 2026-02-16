<?php

namespace App\Filament\Clusters\Finance\Resources\AccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Create')
                ->action('create')
                ->keyBindings(['mod+s']),
            \Filament\Actions\Action::make('create_another')
                ->label('Create & create another')
                ->action('createAnother')
                ->color('gray')
                ->keyBindings(['mod+shift+s']),
            \Filament\Actions\Action::make('cancel')
                ->label('Cancel')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
