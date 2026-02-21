<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\FinanceAccount;
use App\Models\Invoice;
use App\Models\ProductUnit;
use App\Models\Rental;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalCash = FinanceAccount::sum('balance');
        
        // Calculate Receivables (Unpaid Invoices)
        // Assuming status 'paid' means fully paid. 
        // Better: total - paid_amount for all non-cancelled invoices
        $receivables = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sum(function ($invoice) {
                return $invoice->total - $invoice->paid_amount;
            });

        // Deposits held (Active rentals)
        $activeDeposits = Rental::whereIn('status', [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN])
            ->sum('deposit');

        $totalUnits = ProductUnit::count();

        return [
            Stat::make('Total Cash & Bank', 'IDR ' . number_format($totalCash, 0, ',', '.'))
                ->description('Current liquid assets')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Accounts Receivable', 'IDR ' . number_format($receivables, 0, ',', '.'))
                ->description('Unpaid Invoices')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Active Deposits (Liability)', 'IDR ' . number_format($activeDeposits, 0, ',', '.'))
                ->description('Held from customers')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),
                
            Stat::make('Total Inventory Units', $totalUnits)
                ->description('Assets')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
        ];
    }
}
