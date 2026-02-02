<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Illuminate\Console\Command;

class CheckLateRentals extends Command
{
    protected $signature = 'rentals:check-late';

    protected $description = 'Check and update late pickup/return statuses for rentals';

    public function handle(): int
    {
        $this->info('Checking for late rentals...');

        // Check for late pickups
        $latePickups = Rental::where('status', Rental::STATUS_PENDING)
            ->where('start_date', '<', now())
            ->get();

        foreach ($latePickups as $rental) {
            $rental->update(['status' => Rental::STATUS_LATE_PICKUP]);
            $this->line("Rental {$rental->rental_code} marked as late pickup");
        }

        // Check for late returns
        $lateReturns = Rental::where('status', Rental::STATUS_ACTIVE)
            ->where('end_date', '<', now())
            ->get();

        foreach ($lateReturns as $rental) {
            $rental->update(['status' => Rental::STATUS_LATE_RETURN]);
            $this->line("Rental {$rental->rental_code} marked as late return");
        }

        $this->info("Updated {$latePickups->count()} late pickups and {$lateReturns->count()} late returns.");

        return Command::SUCCESS;
    }
}