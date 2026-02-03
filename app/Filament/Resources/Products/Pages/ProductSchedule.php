<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;

class ProductSchedule extends Page
{
    protected static string $resource = ProductResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected string $view = 'filament.resources.products.pages.product-schedule';

    public Product $record;

    public Carbon $startDate;
    public Carbon $endDate;
    public array $days = [];

    public function mount(Product $record): void
    {
        $this->record = $record;
        $this->startDate = now()->startOfMonth();
        $this->endDate = now()->addMonths(2)->endOfMonth();
        $this->calculateDays();
    }

    protected function calculateDays(): void
    {
        $this->days = [];
        $current = $this->startDate->copy();
        while ($current <= $this->endDate) {
            $this->days[] = $current->copy();
            $current->addDay();
        }
    }

    public function nextMonth(): void
    {
        $this->startDate->addMonth();
        $this->endDate->addMonth();
        $this->calculateDays();
    }

    public function previousMonth(): void
    {
        $this->startDate->subMonth();
        $this->endDate->subMonth();
        $this->calculateDays();
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return "Schedule: {$this->record->name}";
    }

    public function getUnitsWithRentals(): array
    {
        $units = $this->record->units()->with(['rentalItems.rental.customer'])->get();
        
        $data = [];
        foreach ($units as $unit) {
            $rentals = [];
            foreach ($unit->rentalItems as $item) {
                $rental = $item->rental;
                // Only include rentals that overlap with our view range
                if ($rental->end_date >= $this->startDate && $rental->start_date <= $this->endDate) {
                    $rentals[] = [
                        'id' => $rental->id,
                        'code' => $rental->rental_code,
                        'customer' => $rental->customer->name,
                        'start' => $rental->start_date,
                        'end' => $rental->end_date,
                        'status' => $rental->status,
                        'color' => \App\Models\Rental::getStatusColor($rental->status),
                    ];
                }
            }
            $data[] = [
                'unit' => $unit,
                'rentals' => $rentals,
            ];
        }
        
        return $data;
    }
}
