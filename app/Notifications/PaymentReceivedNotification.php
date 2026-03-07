<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SaasInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SaasInvoice $invoice,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invoice->tenant;

        return (new MailMessage)
            ->subject('Pembayaran Diterima - '.$this->invoice->invoice_number)
            ->greeting('Halo '.($tenant->name ?? 'Tenant').',')
            ->line('Pembayaran Anda telah berhasil diterima.')
            ->line('**Detail Pembayaran:**')
            ->line('Invoice: '.$this->invoice->invoice_number)
            ->line('Jumlah: Rp '.number_format((float) $this->invoice->total, 0, ',', '.'))
            ->line('Dibayar: '.$this->invoice->paid_at?->format('d M Y H:i'))
            ->line('Terima kasih atas pembayaran Anda. Subscription Anda telah diperpanjang.')
            ->action('Lihat Detail', url('/admin/subscription-billing'));
    }
}
