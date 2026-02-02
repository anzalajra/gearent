<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewRental extends Page
{
    protected static string $resource = RentalResource::class;

    public Rental $record;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.view-rental';
    }

    public function mount(int|string $record): void
    {
        $this->record = Rental::with([
            'customer',
            'items.productUnit.product',
            'items.rentalItemKits.unitKit'
        ])->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'View Rental - ' . $this->record->rental_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn () => RentalResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => $this->record->canBeEdited()),

            DeleteAction::make()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }
}