<?php

namespace App\Filament\Pages;

use App\Models\DocumentType;
use App\Models\Setting;
use App\Models\CustomerCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use UnitEnum;

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

        $this->form->fill([
            ...$settings,
            'document_types' => DocumentType::orderBy('sort_order')->get()->toArray(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([
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
                            ]),

                        Tabs\Tab::make('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                TextInput::make('whatsapp_number')
                                    ->label('WhatsApp Number')
                                    ->placeholder('6281234567890')
                                    ->helperText('Format: country code + number (without + or spaces)')
                                    ->maxLength(20),
                                Checkbox::make('whatsapp_enabled')
                                    ->label('Enable WhatsApp Notifications')
                                    ->helperText('Send rental notifications via WhatsApp'),
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

                        Tabs\Tab::make('Document Types')
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
            ->success()
            ->send();
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