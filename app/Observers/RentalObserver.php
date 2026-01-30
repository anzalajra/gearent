<?php

namespace App\Observers;

use App\Models\Rental;

class RentalObserver
{
    public function created(Rental $rental): void
    {
        // Recalculate after items are saved
        $this->recalculateTotals($rental);
    }

    public function updated(Rental $rental): void
    {
        $this->recalculateTotals($rental);
    }

    protected function recalculateTotals(Rental $rental): void
    {
        $subtotal = $rental->items()->sum('subtotal');
        $total = $subtotal - ($rental->discount ?? 0);

        if ($rental->subtotal != $subtotal || $rental->total != $total) {
            $rental->updateQuietly([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        }
    }
}