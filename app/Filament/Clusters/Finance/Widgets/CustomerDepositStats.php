<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\Rental;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CustomerDepositStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate Total Active Deposits (Held)
        $activeDeposits = Rental::query()
            ->where('security_deposit_status', 'held')
            ->sum('security_deposit_amount');

        // Calculate Pending Deposits (Waiting to be received)
        // We check where status is pending and deposit amount is > 0
        $pendingDeposits = Rental::query()
            ->where('security_deposit_status', 'pending')
            ->where('deposit', '>', 0)
            ->sum('deposit');
            
        // Count Refunded Deposits (Total count since amount is cleared)
        $refundedCount = Rental::query()
            ->where('security_deposit_status', 'refunded')
            ->count();

        return [
            Stat::make('Total Active Deposits', Number::currency($activeDeposits, 'IDR'))
                ->description('Total amount currently held')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Pending Deposits', Number::currency($pendingDeposits, 'IDR'))
                ->description('Amount waiting to be received')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Refunded Deposits', $refundedCount)
                ->description('Total count of refunded deposits')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
