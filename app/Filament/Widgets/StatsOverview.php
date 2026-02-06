<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\ProductUnit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | array | null $columns = [
        'default' => 1,
        'sm' => 2,
        'md' => 3,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        $todayRentals = Rental::whereDate('created_at', today())->count();
        $todayRevenue = Rental::whereDate('created_at', today())->sum('total');
        $activeRentals = Rental::whereIn('status', ['active', 'late_pickup', 'late_return'])->count();
        $pendingRentals = Rental::where('status', 'pending')->count();
        $availableUnits = ProductUnit::where('status', 'available')->count();
        $rentedUnits = ProductUnit::where('status', 'rented')->count();
        $totalCustomers = Customer::count();
        $verifiedCustomers = Customer::where('is_verified', true)->count();

        return [
            Stat::make('Today\'s Rentals', $todayRentals)
                ->description('New bookings today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Today\'s Revenue', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description('Total revenue today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Active Rentals', $activeRentals)
                ->description($pendingRentals . ' pending approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Equipment Status', $availableUnits . ' / ' . ($availableUnits + $rentedUnits))
                ->description($rentedUnits . ' currently rented')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Customers', $totalCustomers)
                ->description($verifiedCustomers . ' verified')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pending Verification', Customer::where('is_verified', false)->count())
                ->description('Awaiting document review')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('danger'),
        ];
    }
}