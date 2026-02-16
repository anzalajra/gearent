<?php

namespace App\Filament\Resources\Invoices\Widgets;

use App\Models\FinanceAccount;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate Total Invoiced (All time or this month? Let's do All Time for now, or maybe filtered by table filter later)
        // For simple overview:
        
        $totalInvoiced = Invoice::sum('total');
        $totalPaid = Invoice::sum('paid_amount') ?? 0; // Assuming paid_amount column exists, otherwise we calculate from transactions
        
        // Wait, does Invoice have 'paid_amount'? 
        // Checking InvoiceResource: "TextInput::make('amount')->default(fn (Invoice $record) => $record->total - $record->paid_amount)"
        // Yes, Invoice has 'paid_amount'.
        
        $outstanding = Invoice::where('status', '!=', Invoice::STATUS_PAID)
            ->where('status', '!=', 'cancelled')
            ->sum(\Illuminate\Support\Facades\DB::raw('total - paid_amount'));

        $totalFinanceBalance = FinanceAccount::where('is_active', true)->sum('balance');

        return [
            Stat::make('Total Invoiced', 'Rp ' . number_format($totalInvoiced, 0, ',', '.'))
                ->description('All time generated invoices')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Outstanding Payment', 'Rp ' . number_format($outstanding, 0, ',', '.'))
                ->description('Waiting for payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Liquid Assets', 'Rp ' . number_format($totalFinanceBalance, 0, ',', '.'))
                ->description('Current balance in all accounts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
