<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\RentalItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

use Illuminate\Contracts\Support\Htmlable;

class SalesByCategoryChart extends ChartWidget
{
    public function getHeading(): string | Htmlable | null
    {
        return 'Revenue by Category';
    }
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Join RentalItem -> ProductUnit -> Product -> Category
        // Sum price from RentalItem
        
        $data = RentalItem::query()
            ->join('product_units', 'rental_items.product_unit_id', '=', 'product_units.id')
            ->join('products', 'product_units.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('sum(rental_items.subtotal) as total_revenue'))
            ->groupBy('categories.name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->pluck('total_revenue')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#6366f1', 
                        '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#84cc16',
                    ],
                ],
            ],
            'labels' => $data->pluck('category_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
