<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionSuspendedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Anda Telah Disuspend - Zewalo')
            ->greeting('Halo '.($this->tenant->name ?? 'Tenant').',')
            ->line('Akun Anda telah disuspend karena belum melakukan pembayaran langganan.')
            ->line('Anda tidak dapat mengakses panel admin hingga langganan diperpanjang.')
            ->line('Silakan segera melakukan pembayaran untuk mengaktifkan kembali akun Anda.')
            ->action('Perpanjang Langganan', url('/admin/subscription-billing'))
            ->line('Jika Anda membutuhkan bantuan, silakan hubungi tim support Zewalo.');
    }
}
