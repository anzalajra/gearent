<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRental extends EditRecord
{
    protected static string $resource = RentalResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Check if rental can be edited
        if (!$this->record->canBeEdited()) {
            Notification::make()
                ->title('Cannot edit this rental')
                ->body('This rental is currently active and cannot be edited.')
                ->danger()
                ->send();

            $this->redirect(RentalResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }
}