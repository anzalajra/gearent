<?php

namespace App\Filament\Central\Widgets;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $totalPlans = SubscriptionPlan::where('is_active', true)->count();

        return [
            Stat::make('Total Tenants', $totalTenants)
                ->description('All registered tenants')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8])
                ->color('primary'),

            Stat::make('Active Tenants', $activeTenants)
                ->description('Paying customers')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Trial Tenants', $trialTenants)
                ->description('In trial period')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Suspended', $suspendedTenants)
                ->description('Suspended accounts')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('danger'),

            Stat::make('Active Plans', $totalPlans)
                ->description('Available subscription plans')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),
        ];
    }
}
