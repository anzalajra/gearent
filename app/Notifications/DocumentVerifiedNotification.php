<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class DocumentVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        if (Setting::get('notification_app_enabled', true)) {
            $channels[] = 'database';
        }
        if (Setting::get('notification_email_enabled', true)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Account Verified')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your documents have been verified and your account is now active.')
                    ->action('Start Renting', url('/'))
                    ->line('Thank you for choosing Gearent!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Account Verified')
            ->body("Your documents have been verified. You can now start renting.")
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->actions([
                \Filament\Actions\Action::make('start_renting')
                    ->button()
                    ->url('/customer/dashboard')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
