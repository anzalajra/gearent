<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Rental;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use UnitEnum;
use Livewire\WithPagination;
use Illuminate\Contracts\Pagination\Paginator;

class Schedule extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|UnitEnum|null $navigationGroup = 'Rentals';
    protected static ?string $navigationLabel = 'Schedule';
    protected static ?string $title = 'Schedule';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.schedule';

    public string $activeTab = 'order'; // 'order' or 'unit'

    public ?string $search = '';
    public int $perPage = 15;

    protected $queryString = [
        'activeTab' => ['except' => 'order'],
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public Carbon $startDate;
    public Carbon $endDate;
    public array $days = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth();
        $this->endDate = now()->addMonths(2)->endOfMonth();
        $this->calculateDays();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // Global Product Schedule Logic
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

    public function viewRentalDetailsAction(): Action
    {
        return Action::make('viewRentalDetails')
            ->modalHeading('Rental Details')
            ->modalWidth('2xl')
            ->form(fn (array $arguments) => [
                Grid::make(2)
                    ->schema([
                        TextInput::make('rental_code')
                            ->label('Rental Code')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        TextInput::make('customer_name')
                            ->label('Customer')
                            ->disabled(),
                        TextInput::make('total')
                            ->label('Total Amount')
                            ->disabled(),
                        TextInput::make('start_date')
                            ->label('Start Date')
                            ->disabled(),
                        TextInput::make('end_date')
                            ->label('End Date')
                            ->disabled(),
                        Textarea::make('items')
                            ->label('Rented Units')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
            ])
            ->fillForm(function (array $arguments) {
                $rental = Rental::with(['customer', 'items.productUnit.product'])->find($arguments['rentalId']);
                if (!$rental) return [];

                $items = $rental->items->map(function ($item) {
                    $pu = $item->productUnit;
                    return ($pu?->product?->name ?? '-') . ' (' . ($pu->serial_number ?? '-') . ')';
                })->join("\n");

                return [
                    'rental_code' => $rental->rental_code,
                    'status' => ucfirst($rental->status),
                    'customer_name' => $rental->customer->name,
                    'total' => 'Rp ' . number_format($rental->total, 0, ',', '.'),
                    'start_date' => $rental->start_date->format('d M Y H:i'),
                    'end_date' => $rental->end_date->format('d M Y H:i'),
                    'items' => $items,
                    'notes' => $rental->notes,
                ];
            })
            ->modalFooterActions(fn (array $arguments) => [
                Action::make('viewRentalPage')
                    ->label('View Rental')
                    ->color('primary')
                    ->url(fn () => "/admin/rentals/{$arguments['rentalId']}/view"),
            ]);
    }

    public function getProductsWithUnitsAndRentals(): Paginator
    {
        $query = Product::with(['units.rentalItems.rental.customer'])
            ->whereHas('units');

        $search = trim($this->search ?? '');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhereHas('units', function ($q) use ($search) {
                      $q->where('serial_number', 'like', '%' . $search . '%');
                  });
            });
        }

        $products = $query->paginate($this->perPage);
        
        $products->getCollection()->transform(function ($product) {
            $productData = [
                'product' => $product,
                'units' => [],
            ];

            foreach ($product->units as $unit) {
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
                $productData['units'][] = [
                    'unit' => $unit,
                    'rentals' => $rentals,
                ];
            }
            return $productData;
        });
        
        return $products;
    }
}
