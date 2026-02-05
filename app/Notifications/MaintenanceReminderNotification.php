<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class MaintenanceReminderNotification extends Notification
{
    use Queueable;

    public $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        if (Setting::get('notification_app_enabled', true)) {
            $channels[] = 'database';
        }
        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Maintenance Reminder')
            ->body("{$this->count} items require maintenance.")
            ->icon('heroicon-o-wrench')
            ->color('warning')
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->button()
                    ->url('/admin/product-units?tableFilters[status][value]=maintenance')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
