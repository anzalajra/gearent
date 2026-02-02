<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
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
        return [];
    }
}