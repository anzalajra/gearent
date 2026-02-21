<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;

class TaxReportService
{
    /**
     * Generate Tax Report for a given period.
     * Calculates PPN Output (Pajak Keluaran) from Invoices.
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public static function generate(Carbon $startDate, Carbon $endDate): array
    {
        // Query Invoices within the period
        // Logic: Tax is usually reported based on Invoice Date (Factur Pajak Date)
        $invoices = Invoice::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_taxable', true)
            ->whereNotIn('status', ['draft', 'cancelled']) // Exclude Drafts and Cancelled
            ->get();

        $totalPpn = $invoices->sum('ppn_amount');
        $totalTaxBase = $invoices->sum('subtotal'); // Assuming subtotal is DPP (Dasar Pengenaan Pajak)
        $totalTotal = $invoices->sum('total');

        return [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'total_tax_base' => $totalTaxBase,
            'total_ppn_payable' => $totalPpn, // This matches the test expectation
            'total_sales' => $totalTotal,
            'transaction_count' => $invoices->count(),
        ];
    }
}
