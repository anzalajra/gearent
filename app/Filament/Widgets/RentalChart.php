<?php

namespace App\Filament\Widgets;

use App\Models\Rental;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RentalChart extends ChartWidget
{
    // PERBAIKAN: Hapus kata "static" dari property $heading
    protected ?string $heading = 'Rentals This Month';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get daily rental counts for current month
        $rentals = Rental::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Prepare data for chart
        $labels = [];
        $data = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $labels[] = $date->format('M d');
            $rental = $rentals->firstWhere('date', $date->format('Y-m-d'));
            $data[] = $rental ? $rental->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Rentals',
                    'data' => $data,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}