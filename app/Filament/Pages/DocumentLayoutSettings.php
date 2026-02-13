<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;
use Illuminate\Support\Facades\Cache;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Rental;
use App\Models\Delivery;
use Illuminate\Support\Facades\URL;

class DocumentLayoutSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Document Layout';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.document-layout-settings';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('preview_invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => ($record = Invoice::latest()->first()) ? URL::signedRoute('public-documents.invoice', $record) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn () => !Invoice::exists()),
                
                Action::make('preview_quotation')
                    ->label('Quotation')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => ($record = Quotation::latest()->first()) ? URL::signedRoute('public-documents.quotation', $record) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn () => !Quotation::exists()),

                Action::make('preview_delivery_note')
                    ->label('Delivery Note')
                    ->icon('heroicon-o-truck')
                    ->url(fn () => ($record = Delivery::latest()->first()) ? URL::signedRoute('public-documents.delivery-note', $record) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn () => !Delivery::exists()),

                Action::make('preview_checklist')
                    ->label('Checklist Form')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn () => ($record = Rental::latest()->first()) ? URL::signedRoute('public-documents.rental.checklist', $record) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn () => !Rental::exists()),
            ])
            ->label('Preview Document')
            ->icon('heroicon-m-eye')
            ->button(),
        ];
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::where('key', 'like', 'doc_%')->pluck('value', 'key')->toArray();
        
        // Set defaults if not present
        $defaults = [
            'doc_font_family' => 'DejaVu Sans',
            'doc_primary_color' => '#2563eb',
            'doc_secondary_color' => '#f3f4f6',
            'doc_show_logo' => true,
            'doc_table_striped' => false,
            'doc_table_bordered' => false,
            'doc_qr_delivery_note' => true,
            'doc_qr_checklist_form' => true,
        ];

        $this->form->fill(array_merge($defaults, $settings));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Document Layout')
                    ->tabs([
                        Tab::make('Branding & Style')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Visual Identity')
                                            ->schema([
                                                FileUpload::make('doc_logo')
                                                    ->label('Document Logo')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('settings')
                                                    ->visibility('public'),
                                                Toggle::make('doc_show_logo')
                                                    ->label('Show Logo on Documents')
                                                    ->default(true),
                                                Select::make('doc_font_family')
                                                    ->label('Font Family')
                                                    ->options([
                                                        'DejaVu Sans' => 'DejaVu Sans (Default)',
                                                        'Helvetica' => 'Helvetica',
                                                        'Arial' => 'Arial',
                                                        'Times New Roman' => 'Times New Roman',
                                                        'Courier' => 'Courier',
                                                    ])
                                                    ->default('DejaVu Sans')
                                                    ->required(),
                                            ]),
                                        
                                        Section::make('Colors')
                                            ->schema([
                                                ColorPicker::make('doc_primary_color')
                                                    ->label('Primary Color')
                                                    ->helperText('Used for headers, borders, and accents')
                                                    ->default('#2563eb')
                                                    ->required(),
                                                ColorPicker::make('doc_secondary_color')
                                                    ->label('Secondary Color')
                                                    ->helperText('Used for backgrounds and subtle elements')
                                                    ->default('#f3f4f6')
                                                    ->required(),
                                            ]),

                                        Section::make('Table Options')
                                            ->schema([
                                                Toggle::make('doc_table_striped')
                                                    ->label('Striped Rows'),
                                                Toggle::make('doc_table_bordered')
                                                    ->label('Bordered Table'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Company Information')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('doc_company_name')
                                            ->label('Company Name')
                                            ->placeholder('e.g. Gearent Inc.'),
                                        TextInput::make('doc_company_phone')
                                            ->label('Phone')
                                            ->tel(),
                                        TextInput::make('doc_company_email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('doc_company_website')
                                            ->label('Website')
                                            ->url(),
                                        TextInput::make('doc_company_tax_id')
                                            ->label('Tax ID / NPWP'),
                                        Textarea::make('doc_company_address')
                                            ->label('Address')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Document Content')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                RichEditor::make('doc_header_text')
                                    ->label('Custom Header Text')
                                    ->helperText('Additional text to display in the header (e.g., branch info)')
                                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList']),
                                
                                RichEditor::make('doc_footer_text')
                                    ->label('Custom Footer Text')
                                    ->helperText('Text to display at the bottom of every page')
                                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList']),

                                RichEditor::make('doc_quotation_terms')
                                    ->label('Quotation Terms & Conditions')
                                    ->helperText('Default terms displayed on quotations')
                                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                                    
                                RichEditor::make('doc_bank_details')
                                    ->label('Bank Account Details')
                                    ->helperText('Payment instructions displayed on Invoices')
                                    ->toolbarButtons(['bold', 'italic', 'bulletList']),
                            ]),

                        Tab::make('QR Code')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Section::make('QR Code Visibility')
                                    ->description('Manage where QR codes should appear on generated documents')
                                    ->schema([
                                        Toggle::make('doc_qr_delivery_note')
                                            ->label('Show QR Code on Delivery Note (Surat Jalan)')
                                            ->helperText('Enables scanning for Delivery IN/OUT operations')
                                            ->default(true),
                                        Toggle::make('doc_qr_checklist_form')
                                            ->label('Show QR Code on Checklist Form')
                                            ->helperText('Enables scanning to view Rental details')
                                            ->default(true),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Handle file upload array
            if (is_array($value)) {
                $value = array_values($value)[0] ?? null;
            }
            
            Setting::set($key, $value);
        }

        Cache::forget('document_settings');

        Notification::make()
            ->title('Document layout settings saved successfully')
            ->success()
            ->send();
    }
}
