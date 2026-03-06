<?php

namespace App\Filament\Central\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class EmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Email Settings';

    protected string $view = 'filament.central.pages.email-settings';

    public ?array $data = [];

    protected static function getSettingsPath(): string
    {
        return storage_path('app/mail-settings.json');
    }

    public static function loadSettings(): array
    {
        $path = static::getSettingsPath();

        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
            if (is_array($settings)) {
                return $settings;
            }
        }

        return [];
    }

    protected static function saveSettingsToFile(array $settings): void
    {
        $dir = dirname(static::getSettingsPath());
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(static::getSettingsPath(), json_encode($settings, JSON_PRETTY_PRINT));
    }

    public function mount(): void
    {
        $saved = static::loadSettings();

        $this->form->fill([
            'mail_mailer' => $saved['mail_mailer'] ?? config('mail.default'),
            'mail_host' => $saved['mail_host'] ?? config('mail.mailers.smtp.host'),
            'mail_port' => $saved['mail_port'] ?? config('mail.mailers.smtp.port'),
            'mail_username' => $saved['mail_username'] ?? config('mail.mailers.smtp.username'),
            'mail_password' => $saved['mail_password'] ?? '',
            'mail_encryption' => $saved['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
            'mail_from_address' => $saved['mail_from_address'] ?? config('mail.from.address'),
            'mail_from_name' => $saved['mail_from_name'] ?? config('mail.from.name'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Email Delivery Settings')
                    ->description('Configure SMTP settings for all tenant email delivery. These settings apply globally to all tenants.')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'mailgun' => 'Mailgun',
                                'ses' => 'Amazon SES',
                                'postmark' => 'Postmark',
                                'log' => 'Log (Testing)',
                            ])
                            ->default('smtp')
                            ->helperText('Driver untuk pengiriman email'),
                        TextInput::make('mail_host')
                            ->label('Host')
                            ->placeholder('smtp.gmail.com'),
                        TextInput::make('mail_port')
                            ->label('Port')
                            ->placeholder('587')
                            ->numeric(),
                        TextInput::make('mail_username')
                            ->label('Username')
                            ->placeholder('your-email@gmail.com'),
                        TextInput::make('mail_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->helperText('Untuk Gmail, gunakan App Password'),
                        Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls'),
                        TextInput::make('mail_from_address')
                            ->label('Default From Address')
                            ->placeholder('noreply@example.com')
                            ->email()
                            ->helperText('Fallback jika tenant belum mengatur alamat pengirim sendiri'),
                        TextInput::make('mail_from_name')
                            ->label('Default From Name')
                            ->placeholder('Zewalo')
                            ->helperText('Fallback jika tenant belum mengatur nama pengirim sendiri'),
                        Actions::make([
                            Action::make('testEmail')
                                ->label('Send Test Email')
                                ->icon('heroicon-o-paper-airplane')
                                ->color('info')
                                ->form([
                                    TextInput::make('test_email_recipient')
                                        ->label('Email Recipient')
                                        ->email()
                                        ->required()
                                        ->helperText('Email address to send test email to'),
                                ])
                                ->action(function (array $data) {
                                    $this->sendTestEmail($data['test_email_recipient']);
                                }),
                        ])->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        static::saveSettingsToFile($data);

        // Apply immediately to current request
        $this->applyMailConfig($data);

        Notification::make()
            ->title('Email settings saved successfully')
            ->success()
            ->send();
    }

    public function sendTestEmail(string $recipient): void
    {
        try {
            // Save first, then apply
            $data = $this->form->getState();
            static::saveSettingsToFile($data);
            $this->applyMailConfig($data);

            $subject = 'Test Email - ' . config('app.name');
            $body = 'This is a test email from ' . config('app.name') . ".\n\n";
            $body .= "If you received this email, your email configuration is working correctly.\n\n";
            $body .= 'Sent at: ' . now()->format('Y-m-d H:i:s');

            Mail::raw($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                    ->subject($subject);
            });

            Notification::make()
                ->title('Test email sent successfully!')
                ->body("Email sent to {$recipient}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send test email')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function applyMailConfig(array $data): void
    {
        $mailer = $data['mail_mailer'] ?? 'smtp';
        $encryption = $data['mail_encryption'] ?? 'tls';

        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.host', $data['mail_host'] ?? null);
        Config::set('mail.mailers.smtp.port', $data['mail_port'] ?? 587);
        Config::set('mail.mailers.smtp.username', $data['mail_username'] ?? null);
        Config::set('mail.mailers.smtp.password', $data['mail_password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
        Config::set('mail.from.address', $data['mail_from_address'] ?? null);
        Config::set('mail.from.name', $data['mail_from_name'] ?? config('app.name'));
    }
}
