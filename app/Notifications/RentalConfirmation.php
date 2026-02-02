<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Rental $rental
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rental = $this->rental->load(['items.productUnit.product']);

        return (new MailMessage)
            ->subject('Booking Confirmation - ' . $this->rental->rental_code)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your rental booking has been received.')
            ->line('**Booking Details:**')
            ->line('Rental Code: ' . $this->rental->rental_code)
            ->line('Start Date: ' . $this->rental->start_date->format('d M Y H:i'))
            ->line('End Date: ' . $this->rental->end_date->format('d M Y H:i'))
            ->line('Total: Rp ' . number_format($this->rental->total, 0, ',', '.'))
            ->line('Deposit: Rp ' . number_format($this->rental->deposit, 0, ',', '.'))
            ->action('View Booking', route('customer.rental.detail', $this->rental->id))
            ->line('We will contact you shortly to confirm your booking.')
            ->line('Thank you for choosing our service!');
    }
}