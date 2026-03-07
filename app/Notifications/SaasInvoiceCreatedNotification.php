<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SaasInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaasInvoiceCreatedNotification extends Notification
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
            ->subject('Invoice Langganan Baru - '.$this->invoice->invoice_number)
            ->greeting('Halo '.($tenant->name ?? 'Tenant').',')
            ->line('Invoice langganan baru telah dibuat.')
            ->line('**Detail Invoice:**')
            ->line('Nomor: '.$this->invoice->invoice_number)
            ->line('Jumlah: Rp '.number_format((float) $this->invoice->total, 0, ',', '.'))
            ->line('Jatuh Tempo: '.$this->invoice->due_at->format('d M Y'))
            ->line('Silakan lakukan pembayaran sebelum jatuh tempo untuk menghindari gangguan layanan.')
            ->action('Bayar Sekarang', url('/admin/subscription-billing'));
    }
}
