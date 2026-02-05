<?php

namespace App\Observers;

use App\Models\Rental;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Support\Facades\Notification;

class RentalObserver
{
    public function created(Rental $rental): void
    {
        // Recalculate after items are saved
        $this->recalculateTotals($rental);

        // Notify Admins
        $admins = User::all();
        Notification::send($admins, new NewBookingNotification($rental));
    }

    public function updated(Rental $rental): void
    {
        $this->recalculateTotals($rental);

        // Notify Customer if status changed to confirmed
        if ($rental->isDirty('status') && $rental->status === 'confirmed') {
            if ($rental->customer) {
                $rental->customer->notify(new BookingConfirmedNotification($rental));
            }
        }
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