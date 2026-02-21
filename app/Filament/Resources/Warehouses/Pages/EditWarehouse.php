<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->formId('form'),
            $this->getCancelFormAction(),
            
            Action::make('toggle_active')
                ->label('Active')
                ->view('filament.actions.header-toggle', [
                    'isOn' => $this->getRecord()->is_active,
                    'label' => 'Active',
                ])
                ->action(function () {
                    $this->getRecord()->update(['is_active' => !$this->getRecord()->is_active]);
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),

            Action::make('toggle_rental')
                ->label('Rental Available')
                ->view('filament.actions.header-toggle', [
                    'isOn' => $this->getRecord()->is_available_for_rental,
                    'label' => 'Rental Available',
                ])
                ->action(function () {
                    $this->getRecord()->update(['is_available_for_rental' => !$this->getRecord()->is_available_for_rental]);
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),

            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
