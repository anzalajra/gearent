<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewRental extends Page
{
    protected static string $resource = RentalResource::class;

    protected static bool $canCreateAnother = false;

    public ?Rental $rental = null;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.view-rental';
    }

    public function mount(int|string $record): void
    {
        $this->rental = Rental::with([
            'customer',
            'items.productUnit.product',
            'items.rentalItemKits.unitKit'
        ])->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'View Rental - ' . $this->rental->rental_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->record($this->rental)
                ->visible(fn () => $this->rental->canBeEdited()),

            Action::make('rental_documents')
                ->label('Delivery Documents')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn () => RentalResource::getUrl('documents', ['record' => $this->rental])),

            Action::make('pickup')
                ->label('Process Pickup')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->url(fn () => RentalResource::getUrl('pickup', ['record' => $this->rental]))
                ->visible(fn () => in_array($this->rental->status, [Rental::STATUS_PENDING, Rental::STATUS_LATE_PICKUP])),

            Action::make('return')
                ->label('Process Return')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->url(fn () => RentalResource::getUrl('return', ['record' => $this->rental]))
                ->visible(fn () => in_array($this->rental->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN])),
        ];
    }
}