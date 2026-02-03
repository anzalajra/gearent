<?php

namespace App\Filament\Pages;

use App\Models\DocumentType;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                                        TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->maxLength(255),
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
                            ]),

                        Tabs\Tab::make('Rental')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('deposit_percentage')
                                            ->label('Deposit Percentage')
                                            ->numeric()
                                            ->suffix('%')
                                            ->required()
                                            ->default(30)
                                            ->minValue(0)
                                            ->maxValue(100),
                                        TextInput::make('late_fee_percentage')
                                            ->label('Late Fee per Day')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->minValue(0),
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

        // Handle General/Rental/WhatsApp Settings
        $settingsData = collect($data)->except('document_types')->toArray();
        foreach ($settingsData as $key => $value) {
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