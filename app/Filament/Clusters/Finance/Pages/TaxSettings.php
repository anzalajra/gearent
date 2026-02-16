<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TaxSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = FinanceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Tax Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.clusters.finance.pages.tax-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::where('group', 'tax')->get()->pluck('value', 'key')->toArray();
        
        // Decode JSON fields if they are strings
        if (isset($settings['international_tax_rates']) && is_string($settings['international_tax_rates'])) {
            $settings['international_tax_rates'] = json_decode($settings['international_tax_rates'], true) ?? [];
        }

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Global Tax Configuration')
                    ->schema([
                        Toggle::make('tax_enabled')
                            ->label('Enable Tax System')
                            ->helperText('Turn off if you do not want to use tax features in the system.')
                            ->default(true)
                            ->live(),
                    ]),

                Section::make('Tax System Mode')
                    ->visible(fn ($get) => $get('tax_enabled'))
                    ->schema([
                        Radio::make('tax_mode')
                            ->label('Select Tax System')
                            ->options([
                                'indonesia' => 'Indonesia (PPN & PPh Final)',
                                'international' => 'International (Multi-Tax Rates)',
                            ])
                            ->default('indonesia')
                            ->required()
                            ->live(),
                    ]),

                Section::make('Company Tax Identity')
                    ->visible(fn ($get) => $get('tax_enabled'))
                    ->description('Manage your company tax information (Master Data Perpajakan)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_tax_name')
                                ->label('Company Name (Tax)')
                                ->placeholder('Nama Sesuai KTP/Paspor/Akta')
                                ->required(),
                            TextInput::make('company_npwp')
                                ->label('NPWP')
                                ->placeholder('Nomor Pokok Wajib Pajak'),
                            TextInput::make('company_nik')
                                ->label('NIK')
                                ->placeholder('Nomor Induk Kependudukan'),
                            TextInput::make('company_tax_address')
                                ->label('Tax Address')
                                ->columnSpanFull(),
                        ]),
                    ]),
                
                Section::make('Indonesia Tax Configuration')
                    ->visible(fn ($get) => $get('tax_enabled') && $get('tax_mode') === 'indonesia')
                    ->schema([
                        Toggle::make('is_pkp')
                            ->label('Pengusaha Kena Pajak (PKP)')
                            ->helperText('Enable if your business is registered as PKP and can issue Faktur Pajak.')
                            ->live(),

                        Toggle::make('is_taxable')
                            ->label('Kena PPN (11%) (Default)')
                            ->helperText('Default setting for new transactions.')
                            ->default(true),

                        Toggle::make('price_includes_tax')
                            ->label('Harga Termasuk Pajak (Default)')
                            ->helperText('Default setting for new transactions.')
                            ->default(false),
                        
                        FileUpload::make('digital_certificate')
                            ->label('Digital Certificate (e-Faktur)')
                            ->helperText('Upload your digital certificate (.p12/.pfx) for e-Faktur integration.')
                            ->disk('local')
                            ->directory('certificates')
                            ->visible(fn ($get) => $get('is_pkp')),
                            
                        Grid::make(2)->schema([
                            TextInput::make('ppn_rate')
                                ->label('Default PPN Rate (%)')
                                ->numeric()
                                ->default(11)
                                ->suffix('%')
                                ->visible(fn ($get) => $get('is_pkp')),
                                
                            TextInput::make('pph_final_rate')
                                ->label('PPh Final Rate (%)')
                                ->numeric()
                                ->default(0.5)
                                ->suffix('%')
                                ->helperText('For UMKM (PP 55/2022)'),
                        ]),
                    ]),

                Section::make('International Tax Configuration')
                    ->visible(fn ($get) => $get('tax_enabled') && $get('tax_mode') === 'international')
                    ->schema([
                        Repeater::make('international_tax_rates')
                            ->label('Tax Rates by Country/Region')
                            ->schema([
                                Select::make('country_code')
                                    ->label('Country')
                                    ->options([
                                        'SG' => 'Singapore',
                                        'MY' => 'Malaysia',
                                        'US' => 'United States',
                                        'UK' => 'United Kingdom',
                                        'AU' => 'Australia',
                                        'JP' => 'Japan',
                                        'CN' => 'China',
                                        'IN' => 'India',
                                        'TH' => 'Thailand',
                                        'VN' => 'Vietnam',
                                        'PH' => 'Philippines',
                                        // Add more as needed or use a country list package
                                    ])
                                    ->searchable()
                                    ->required(),
                                TextInput::make('tax_name')
                                    ->label('Tax Name')
                                    ->placeholder('e.g. VAT, GST, Sales Tax')
                                    ->required(),
                                TextInput::make('rate')
                                    ->label('Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Tax Rate'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $type = 'string';
            $originalValue = $value;

            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_numeric($value)) {
                $type = 'number';
            } elseif (is_array($value)) {
                $type = 'json';
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'tax',
                    'type' => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                ]
            );
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
