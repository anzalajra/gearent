<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notification & WhatsApp';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.clusters.settings.pages.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Channels')
                    ->schema([
                        Toggle::make('notification_app_enabled')
                            ->label('Enable In-App Notifications')
                            ->default(true),
                        Toggle::make('notification_email_enabled')
                            ->label('Enable Email Notifications')
                            ->default(true)
                            ->live(),
                        Toggle::make('whatsapp_enabled')
                            ->label('Enable Send via WhatsApp')
                            ->helperText('Tampilkan tombol kirim via WhatsApp di detail rental, invoice, dll')
                            ->default(false),
                    ])->columns(3),

                Section::make('Notification Types')
                    ->description('Select which events trigger email notifications to admin/staff.')
                    ->visible(fn ($get) => $get('notification_email_enabled'))
                    ->schema([
                        Toggle::make('notify_new_customer')
                            ->label('New Customer Registration')
                            ->helperText('When a new customer registers')
                            ->default(true),
                        Toggle::make('notify_verification_request')
                            ->label('Customer Verification Request')
                            ->helperText('When customer uploads documents for verification')
                            ->default(true),
                        Toggle::make('notify_new_rental')
                            ->label('New Rental Order (Quotation)')
                            ->helperText('When a new rental/quotation is created')
                            ->default(true),
                        Toggle::make('notify_new_invoice')
                            ->label('New Invoice')
                            ->helperText('When a new invoice is generated')
                            ->default(true),
                        Toggle::make('notify_delivery_out')
                            ->label('Delivery Out (Surat Jalan Keluar)')
                            ->helperText('When items are delivered to customer')
                            ->default(true),
                        Toggle::make('notify_delivery_in')
                            ->label('Delivery In (Surat Jalan Masuk)')
                            ->helperText('When items are returned from customer')
                            ->default(true),
                        Toggle::make('notify_rental_completed')
                            ->label('Rental Completed')
                            ->helperText('When a rental is marked as completed')
                            ->default(true),
                    ])->columns(2),

                Section::make('Email Settings')
                    ->description('Configure SMTP settings for email notifications.')
                    ->visible(fn ($get) => $get('notification_email_enabled'))
                    ->schema([
                        \Filament\Forms\Components\Select::make('mail_mailer')
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
                        \Filament\Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls'),
                        TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->placeholder('noreply@example.com')
                            ->email(),
                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->placeholder('Gearent'),
                    ])->columns(2),

                Section::make('WhatsApp Templates')
                    ->description('Template pesan untuk tombol Send via WhatsApp. Gunakan placeholder sesuai konteks.')
                    ->visible(fn ($get) => $get('whatsapp_enabled'))
                    ->schema([
                        Textarea::make('whatsapp_template_rental_detail')
                            ->label('Rental Detail Template')
                            ->helperText('Placeholders: [customer_name], [rental_ref], [items_list], [pickup_date], [return_date], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_quotation')
                            ->label('Quotation Template')
                            ->helperText('Placeholders: [customer_name], [quotation_ref], [total_amount], [valid_until], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_invoice')
                            ->label('Invoice Template')
                            ->helperText('Placeholders: [customer_name], [invoice_ref], [total_amount], [due_date], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_rental_delivery_out')
                            ->label('Delivery (Out/To Customer) Template')
                            ->helperText('Placeholders: [customer_name], [rental_ref], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_rental_delivery_in')
                            ->label('Delivery (In/Return) Template')
                            ->helperText('Placeholders: [customer_name], [rental_ref], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_rental_pickup')
                            ->label('Pickup Reminder Template')
                            ->helperText('Placeholders: [customer_name], [rental_ref], [pickup_date], [link_pdf], [company_name]')
                            ->rows(3),
                        Textarea::make('whatsapp_template_rental_return')
                            ->label('Return Reminder Template')
                            ->helperText('Placeholders: [customer_name], [rental_ref], [return_date], [link_pdf], [company_name]')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
