<?php

namespace App\Notifications;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        if (! (tenant()?->hasFeature(\App\Enums\TenantFeature::EmailNotification) ?? true)) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));

        $subject = 'Reset Password - '.config('app.name');

        try {
            $mail = (new MailMessage)
                ->subject($subject)
                ->greeting('Hello '.$notifiable->name.'!')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('This password reset link will expire in 60 minutes.')
                ->line('If you did not request a password reset, no further action is required.')
                ->salutation('Regards, '.config('app.name'));

            // Log successful send attempt
            EmailLog::logSent(
                to: $notifiable->email,
                subject: $subject,
                mailableClass: self::class,
                userId: $notifiable->id
            );

            return $mail;
        } catch (\Exception $e) {
            // Log failed send
            EmailLog::logFailed(
                to: $notifiable->email,
                subject: $subject,
                mailableClass: self::class,
                errorMessage: $e->getMessage(),
                userId: $notifiable->id
            );
            throw $e;
        }
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
