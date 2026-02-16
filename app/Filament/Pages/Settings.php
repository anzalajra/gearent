<?php

namespace App\Filament\Pages;

use App\Models\DocumentType;
use App\Models\Setting;
use App\Models\CustomerCategory;
use App\Models\FinanceTransaction;
use App\Services\JournalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use UnitEnum;

use Illuminate\Support\Facades\Log;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        
        // Decode JSON settings
        if (isset($settings['registration_custom_fields'])) {
            $settings['registration_custom_fields'] = json_decode($settings['registration_custom_fields'], true);
        }
        if (isset($settings['operational_days'])) {
            $settings['operational_days'] = json_decode($settings['operational_days'], true);
        }
        if (isset($settings['holidays'])) {
            $settings['holidays'] = json_decode($settings['holidays'], true);
        }

        $this->form->fill([
            ...$settings,
            'document_types' => DocumentType::orderBy('sort_order')->get()->toArray(),
        ]);

        if (session()->has('show_sync_confirmation')) {
            Notification::make()
                ->title('Switched to Advanced Mode')
                ->body('Do you want to sync all existing simple transactions to journal entries?')
                ->warning()
                ->persistent()
                ->actions([
                    Action::make('sync')
                        ->button()
                        ->label('Sync Now')
                        ->dispatch('syncSimpleTransactions'),
                    Action::make('close')
                        ->label('Later')
                        ->close(),
                ])
                ->send();

            session()->forget('show_sync_confirmation');
        }
    }

    protected $listeners = ['syncSimpleTransactions' => 'syncSimpleTransactions'];

    public function syncSimpleTransactions(): void
    {
        $count = 0;
        FinanceTransaction::chunk(100, function ($transactions) use (&$count) {
            foreach ($transactions as $transaction) {
                JournalService::syncFromTransaction($transaction);
                $count++;
            }
        });

        Notification::make()
            ->title("Synced {$count} transactions to Journal Entries")
            ->success()
            ->send();
            
        $this->redirect(request()->header('Referer'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Appearance')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                ToggleButtons::make('theme_preset')
                                    ->label('Theme Preset')
                                    ->options([
                                        'default' => new HtmlString('<div class="w-6 h-6 rounded-full bg-gray-900 border border-gray-200" title="Default"></div>'),
                                        'slate' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #64748b;" title="Slate"></div>'),
                                        'gray' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #6b7280;" title="Gray"></div>'),
                                        'zinc' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #71717a;" title="Zinc"></div>'),
                                        'neutral' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #737373;" title="Neutral"></div>'),
                                        'stone' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #78716c;" title="Stone"></div>'),
                                        'red' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #ef4444;" title="Red"></div>'),
                                        'orange' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #f97316;" title="Orange"></div>'),
                                        'amber' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #f59e0b;" title="Amber"></div>'),
                                        'yellow' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #eab308;" title="Yellow"></div>'),
                                        'lime' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #84cc16;" title="Lime"></div>'),
                                        'green' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #22c55e;" title="Green"></div>'),
                                        'emerald' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #10b981;" title="Emerald"></div>'),
                                        'teal' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #14b8a6;" title="Teal"></div>'),
                                        'cyan' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #06b6d4;" title="Cyan"></div>'),
                                        'sky' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #0ea5e9;" title="Sky"></div>'),
                                        'blue' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #3b82f6;" title="Blue"></div>'),
                                        'indigo' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #6366f1;" title="Indigo"></div>'),
                                        'violet' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #8b5cf6;" title="Violet"></div>'),
                                        'purple' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #a855f7;" title="Purple"></div>'),
                                        'fuchsia' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #d946ef;" title="Fuchsia"></div>'),
                                        'pink' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #ec4899;" title="Pink"></div>'),
                                        'rose' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background-color: #f43f5e;" title="Rose"></div>'),
                                        'custom' => new HtmlString('<div class="w-6 h-6 rounded-full" style="background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red);" title="Custom"></div>'),
                                    ])
                                    ->inline()
                                    ->default('default')
                                    ->live()
                                    ->required(),
                                ColorPicker::make('theme_color')
                                    ->label('Custom Color')
                                    ->helperText('Select a custom primary color for the admin panel.')
                                    ->visible(fn ($get) => $get('theme_preset') === 'custom')
                                    ->required(fn ($get) => $get('theme_preset') === 'custom')
                                    ->columnSpanFull(),
                                ToggleButtons::make('navigation_layout')
                                    ->label('Navigation Layout')
                                    ->options([
                                        'sidebar' => 'Sidebar',
                                        'top' => 'Top Navigation',
                                    ])
                                    ->icons([
                                        'sidebar' => 'heroicon-o-bars-3-bottom-left',
                                        'top' => 'heroicon-o-bars-3',
                                    ])
                                    ->default('sidebar')
                                    ->inline()
                                    ->required(),
                            ]),
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        FileUpload::make('site_logo')
                                            ->label('Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->columnSpanFull(),
                                        TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->maxLength(255),
                                        Toggle::make('site_name_in_header')
                                            ->label('Show Site Name in Header')
                                            ->default(true),
                                        TextInput::make('site_tagline')
                                            ->label('Tagline')
                                            ->maxLength(255),
                                        TextInput::make('site_email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('site_phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        ToggleButtons::make('finance_mode')
                                            ->label('Finance Mode')
                                            ->options([
                                                'simple' => 'Simple (Income/Expense)',
                                                'advanced' => 'Advanced (Journal Entries)',
                                            ])
                                            ->default('advanced')
                                            ->inline()
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                                Textarea::make('site_address')
                                    ->label('Address')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                TextInput::make('site_copyright')
                                    ->label('Footer Copyright Text')
                                    ->placeholder('e.g. © 2024 Gearent. All rights reserved.')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Rental')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Deposit Settings')
                                            ->schema([
                                                Checkbox::make('deposit_enabled')
                                                    ->label('Enable Deposit')
                                                    ->default(true)
                                                    ->live(),
                                                Grid::make(2)
                                                    ->visible(fn ($get) => $get('deposit_enabled'))
                                                    ->schema([
                                                        Select::make('deposit_type')
                                                            ->options([
                                                                'percentage' => 'Percentage (%)',
                                                                'fixed' => 'Fixed Amount (Rp)',
                                                            ])
                                                            ->default('percentage')
                                                            ->live()
                                                            ->required(),
                                                        TextInput::make('deposit_amount')
                                                            ->label(fn ($get) => $get('deposit_type') === 'percentage' ? 'Percentage' : 'Amount')
                                                            ->numeric()
                                                            ->suffix(fn ($get) => $get('deposit_type') === 'percentage' ? '%' : null)
                                                            ->prefix(fn ($get) => $get('deposit_type') === 'fixed' ? 'Rp' : null)
                                                            ->required()
                                                            ->default(30)
                                                            ->minValue(0)
                                                            ->maxValue(fn ($get) => $get('deposit_type') === 'percentage' ? 100 : null),
                                                    ]),
                                            ])->columnSpanFull(),

                                        Section::make('Late Fee Settings')
                                            ->schema([
                                                Select::make('late_fee_type')
                                                    ->label('Late Fee Type')
                                                    ->options([
                                                        'percentage' => 'Percentage (%)',
                                                        'fixed' => 'Fixed Amount (Rp)',
                                                    ])
                                                    ->default('percentage')
                                                    ->live()
                                                    ->required(),
                                                TextInput::make('late_fee_amount')
                                                    ->label(fn ($get) => $get('late_fee_type') === 'percentage' ? 'Percentage per Day' : 'Amount per Day')
                                                    ->numeric()
                                                    ->suffix(fn ($get) => $get('late_fee_type') === 'percentage' ? '%' : null)
                                                    ->prefix(fn ($get) => $get('late_fee_type') === 'fixed' ? 'Rp' : null)
                                                    ->required()
                                                    ->default(10)
                                                    ->minValue(0),
                                            ])->columnSpanFull(),

                                        TextInput::make('min_rental_days')
                                            ->label('Minimum Rental Days')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1),
                                        TextInput::make('max_rental_days')
                                            ->label('Maximum Rental Days')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(30),
                                    ]),
                                    
                                Section::make('Operational Days & Holidays')
                                    ->schema([
                                        CheckboxList::make('operational_days')
                                            ->label('Operational Days')
                                            ->helperText('Unchecked days will be considered as holidays (closed).')
                                            ->options([
                                                '1' => 'Monday',
                                                '2' => 'Tuesday',
                                                '3' => 'Wednesday',
                                                '4' => 'Thursday',
                                                '5' => 'Friday',
                                                '6' => 'Saturday',
                                                '0' => 'Sunday',
                                            ])
                                            ->default(['1', '2', '3', '4', '5', '6', '0'])
                                            ->columns(4)
                                            ->gridDirection('row')
                                            ->columnSpanFull(),
                                        
                                        Repeater::make('holidays')
                                            ->label('National Holidays / Closed Dates')
                                            ->schema([
                                                DatePicker::make('date')
                                                    ->label('Date')
                                                    ->required()
                                                    ->native(false),
                                                TextInput::make('name')
                                                    ->label('Description')
                                                    ->required(),
                                            ])
                                            ->grid(2)
                                            ->defaultItems(0)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Notification')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Section::make('Channels')
                                    ->schema([
                                        Toggle::make('notification_app_enabled')
                                            ->label('Enable In-App Notifications')
                                            ->default(true),
                                        Toggle::make('notification_email_enabled')
                                            ->label('Enable Email Notifications')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('notification_whatsapp_enabled')
                                            ->label('Enable WhatsApp Notifications')
                                            ->default(false)
                                            ->live(),
                                    ])->columns(3),

                                Section::make('Email Settings')
                                    ->description('Configure SMTP settings for email notifications.')
                                    ->visible(fn ($get) => $get('notification_email_enabled'))
                                    ->schema([
                                        TextInput::make('mail_mailer')
                                            ->label('Mailer')
                                            ->default('smtp')
                                            ->disabled(),
                                        TextInput::make('mail_host')
                                            ->label('Host')
                                            ->placeholder('smtp.mailtrap.io'),
                                        TextInput::make('mail_port')
                                            ->label('Port')
                                            ->placeholder('2525')
                                            ->numeric(),
                                        TextInput::make('mail_username')
                                            ->label('Username'),
                                        TextInput::make('mail_password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('mail_encryption')
                                            ->label('Encryption')
                                            ->placeholder('tls'),
                                        TextInput::make('mail_from_address')
                                            ->label('From Address')
                                            ->placeholder('hello@example.com')
                                            ->email(),
                                        TextInput::make('mail_from_name')
                                            ->label('From Name')
                                            ->placeholder('Gearent'),
                                    ])->columns(2),

                                Section::make('WhatsApp Settings')
                                    ->visible(fn ($get) => $get('notification_whatsapp_enabled'))
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('whatsapp_status')
                                            ->label('Status')
                                            ->content('Under Development'),
                                        TextInput::make('whatsapp_number')
                                            ->label('WhatsApp Number')
                                            ->placeholder('6281234567890')
                                            ->helperText('Format: country code + number (without + or spaces)')
                                            ->maxLength(20),
                                    ]),
                            ]),

                        Tabs\Tab::make('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Toggle::make('whatsapp_enabled')
                                    ->label('Enable WhatsApp Feature')
                                    ->default(true)
                                    ->helperText('Enable "Send via WhatsApp" buttons across the admin panel.')
                                    ->live(),

                                Section::make('Message Templates')
                                    ->description('Configure the default message templates for WhatsApp actions.')
                                    ->visible(fn ($get) => $get('whatsapp_enabled'))
                                    ->schema([
                                        Section::make('Rental Templates')
                                            ->description('Available placeholders: [customer_name], [rental_ref], [items_list], [pickup_date], [return_date], [link_pdf], [company_name]')
                                            ->schema([
                                                Textarea::make('whatsapp_template_rental_detail')
                                                    ->label('Rental Detail Message')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nBerikut adalah detail rental Anda [rental_ref].\n\nBarang:\n[items_list]\n\nTanggal Ambil: [pickup_date]\nTanggal Kembali: [return_date]\n\nSilakan unduh dokumen lampiran di sini: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                                Textarea::make('whatsapp_template_rental_pickup')
                                                    ->label('Pickup Reminder / Slip')
                                                    ->helperText('Placeholders: [customer_name], [rental_ref], [pickup_date], [link_pdf], [company_name]')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nIni adalah pengingat untuk pengambilan rental [rental_ref] yang dijadwalkan pada [pickup_date].\n\nSilakan unduh slip pengambilan di sini: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                                Textarea::make('whatsapp_template_rental_return')
                                                    ->label('Return Reminder / Checklist')
                                                    ->helperText('Placeholders: [customer_name], [rental_ref], [return_date], [link_pdf], [company_name]')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nIni adalah pengingat untuk pengembalian rental [rental_ref] sebelum [return_date].\n\nSilakan unduh checklist pengembalian di sini: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                                Textarea::make('whatsapp_template_rental_delivery_in')
                                                    ->label('Delivery (To Customer) Completed')
                                                    ->helperText('Placeholders: [customer_name], [rental_ref], [link_pdf], [company_name]')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nRental Anda [rental_ref] telah dikirimkan.\n\nSurat Jalan: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                                Textarea::make('whatsapp_template_rental_delivery_out')
                                                    ->label('Delivery (Return) Completed')
                                                    ->helperText('Placeholders: [customer_name], [rental_ref], [link_pdf], [company_name]')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nKami telah menjemput rental Anda [rental_ref].\n\nBukti Pengambilan: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                            ])->collapsible(),

                                        Section::make('Finance Templates')
                                            ->description('Available placeholders: [customer_name], [quotation_ref], [invoice_ref], [total_amount], [valid_until], [due_date], [link_pdf], [company_name]')
                                            ->schema([
                                                Textarea::make('whatsapp_template_quotation')
                                                    ->label('Quotation Message')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nBerikut adalah penawaran harga [quotation_ref] yang Anda minta.\n\nTotal: [total_amount]\nBerlaku hingga: [valid_until]\n\nLihat PDF: [link_pdf]\n\nTerima kasih,\n[company_name]"),
                                                Textarea::make('whatsapp_template_invoice')
                                                    ->label('Invoice Message')
                                                    ->rows(4)
                                                    ->default("Halo [customer_name],\n\nBerikut adalah tagihan [invoice_ref] Anda.\n\nTotal: [total_amount]\nJatuh tempo: [due_date]\n\nLihat PDF: [link_pdf]\n\nMohon lakukan pembayaran sebelum tanggal jatuh tempo.\n\nTerima kasih,\n[company_name]"),
                                            ])->collapsible(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Registration')
                            ->icon('heroicon-o-user-plus')
                            ->schema([
                                Toggle::make('registration_open')
                                    ->label('Accept New Registrations')
                                    ->default(true),
                                
                                Toggle::make('auto_verify_registration')
                                    ->label('Auto Verify Email')
                                    ->helperText('If enabled, customers will be verified automatically upon registration.')
                                    ->default(true),

                                Select::make('default_customer_category_id')
                                    ->label('Default Customer Category')
                                    ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable(),

                                Section::make('Custom Registration Fields')
                                    ->description('Add extra fields to the registration form.')
                                    ->schema([
                                        Repeater::make('registration_custom_fields')
                                            ->label('Fields')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('label')->required(),
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->label('Field Key')
                                                        ->helperText('Unique key for database storage (e.g., student_id)'),
                                                ]),
                                                Select::make('type')
                                                    ->options([
                                                        'text' => 'Text',
                                                        'number' => 'Number',
                                                        'select' => 'Select',
                                                        'radio' => 'Radio',
                                                        'checkbox' => 'Checkbox', // For single checkbox (boolean)
                                                        'textarea' => 'Textarea',
                                                    ])
                                                    ->required()
                                                    ->live(),
                                                
                                                Repeater::make('options')
                                                    ->schema([
                                                        TextInput::make('value')->required(),
                                                        TextInput::make('label')->required(),
                                                    ])
                                                    ->columns(2)
                                                    ->visible(fn ($get) => in_array($get('type'), ['select', 'radio'])),
                                                
                                                Grid::make(2)->schema([
                                                    Toggle::make('required'),
                                                    Select::make('visible_for_categories')
                                                        ->multiple()
                                                        ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                                                        ->label('Visible Only for Categories')
                                                        ->placeholder('All Categories'),
                                                ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                                    ]),
                            ]),

                        Tabs\Tab::make('Verification Document')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Repeater::make('document_types')
                                    ->schema([
                                        Hidden::make('id'),
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->required()
                                            ->maxLength(50),
                                        Textarea::make('description')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Checkbox::make('is_required')
                                            ->label('Required for verification'),
                                        Checkbox::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->reorderable('sort_order')
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle JSON fields
        if (isset($data['registration_custom_fields']) && is_array($data['registration_custom_fields'])) {
            $data['registration_custom_fields'] = json_encode($data['registration_custom_fields']);
        }
        if (isset($data['operational_days']) && is_array($data['operational_days'])) {
            $data['operational_days'] = json_encode($data['operational_days']);
        }
        if (isset($data['holidays']) && is_array($data['holidays'])) {
            $data['holidays'] = json_encode($data['holidays']);
        }

        // Check for mode switch
        $oldMode = Setting::get('finance_mode', 'advanced');
        $newMode = $data['finance_mode'] ?? 'advanced';
        
        if ($oldMode === 'simple' && $newMode === 'advanced') {
            session()->put('show_sync_confirmation', true);
        }

        // Handle General/Rental/WhatsApp Settings
        $settingsData = collect($data)->except('document_types')->toArray();
        foreach ($settingsData as $key => $value) {
            if (is_array($value)) {
                $value = array_values($value)[0] ?? null;
            }
            Setting::set($key, $value ?? '');
        }

        // Handle Document Types
        if (isset($data['document_types'])) {
            $newIds = [];
            foreach ($data['document_types'] as $index => $item) {
                $docType = DocumentType::updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'name' => $item['name'],
                        'code' => $item['code'],
                        'description' => $item['description'] ?? null,
                        'is_required' => $item['is_required'] ?? false,
                        'is_active' => $item['is_active'] ?? true,
                        'sort_order' => $index,
                    ]
                );
                
                $newIds[] = $docType->id;
            }
            // Optional: Hapus yang tidak ada di repeater (hati-hati dengan foreign keys)
            DocumentType::whereNotIn('id', $newIds)->delete();
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->body('The page will reload to apply changes.')
            ->success()
            ->send();

        $this->redirect(request()->header('Referer'));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->submit('save'),
        ];
    }
}