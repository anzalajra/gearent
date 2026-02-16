<?php

namespace App\Console\Commands;

use App\Models\DepreciationRun;
use App\Models\ProductUnit;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunMonthlyDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:run-depreciation {--month= : The month to run for (YYYY-MM)} {--force : Force run even if already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and record monthly depreciation for all assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('month') ?? now()->format('Y-m');
        $force = $this->option('force');

        $this->info("Running depreciation for period: $period");

        // Check if already run
        $existingRun = DepreciationRun::where('period', $period)->first();
        if ($existingRun && !$force) {
            $this->error("Depreciation for $period already exists. Use --force to overwrite (will delete old run).");
            return 1;
        }

        if ($existingRun && $force) {
            // Rollback journal entry?
            // Assuming JournalService handles cascading deletes if reference is deleted, or we manually delete.
            // JournalEntry is polymorphic linked to DepreciationRun.
            // But JournalEntry doesn't cascade delete by default unless set up in DB.
            // We should find the journal entry and delete it.
            $journalEntry = \App\Models\JournalEntry::where('reference_type', DepreciationRun::class)
                ->where('reference_id', $existingRun->id)
                ->first();
            
            if ($journalEntry) {
                // Delete items first
                $journalEntry->items()->delete();
                $journalEntry->delete();
            }
            
            $existingRun->delete();
            $this->warn("Deleted existing run for $period.");
        }

        // Calculate Depreciation
        // Get all active units (not retired)
        // We only depreciate units purchased before the end of the target month.
        $targetDate = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
        
        $units = ProductUnit::where('status', '!=', ProductUnit::STATUS_RETIRED)
            ->whereNotNull('purchase_date')
            ->whereNotNull('purchase_price')
            ->where('purchase_date', '<=', $targetDate)
            ->get();

        $totalDepreciation = 0;
        $itemsProcessed = 0;

        foreach ($units as $unit) {
            // Calculate monthly depreciation
            $cost = $unit->purchase_price;
            $residual = $unit->residual_value ?? 0;
            $lifeMonths = $unit->useful_life ?? 60; // Default 5 years
            
            if ($lifeMonths <= 0) continue;

            $monthlyDepreciation = ($cost - $residual) / $lifeMonths;
            
            // Check if fully depreciated
            // Calculate age in months at the END of the target period
            $purchaseDate = $unit->purchase_date;
            $ageMonths = $purchaseDate->diffInMonths($targetDate);
            
            // If unit is older than useful life, no more depreciation (unless we want to handle partial last month?)
            // Simple logic: if age <= life, depreciate.
            // More precise: Check accumulated depreciation so far.
            // For now, simple straight line.
            
            if ($ageMonths < $lifeMonths && $monthlyDepreciation > 0) {
                $totalDepreciation += $monthlyDepreciation;
                $itemsProcessed++;
            }
        }

        $totalDepreciation = round($totalDepreciation, 2);

        if ($totalDepreciation <= 0) {
            $this->info("No depreciation to record for this period.");
            return 0;
        }

        $this->info("Total Depreciation: Rp " . number_format($totalDepreciation, 2));
        $this->info("Items Processed: $itemsProcessed");

        // Record Run
        DB::transaction(function () use ($period, $totalDepreciation, $itemsProcessed, $targetDate) {
            $run = DepreciationRun::create([
                'date' => $targetDate,
                'period' => $period,
                'total_amount' => $totalDepreciation,
                'items_processed' => $itemsProcessed,
                'notes' => "Auto-generated monthly depreciation for $itemsProcessed items.",
            ]);

            // Create Journal Entry
            JournalService::recordSimpleTransaction(
                'MONTHLY_DEPRECIATION',
                $run,
                $totalDepreciation,
                "Beban Penyusutan Peralatan Periode $period"
            );
        });

        $this->info("Depreciation run completed successfully.");
        return 0;
    }
}
