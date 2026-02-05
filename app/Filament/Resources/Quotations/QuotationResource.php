<?php

namespace App\Filament\Resources\Quotations;

use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Resources\Quotations\RelationManagers\RentalsRelationManager;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Details')
                    ->schema([
                        TextInput::make('number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        DatePicker::make('valid_until'),
                        Select::make('status')
                            ->options(Quotation::getStatusOptions())
                            ->required()
                            ->default(Quotation::STATUS_ON_QUOTE),
                        TextInput::make('total')
                            ->disabled()
                            ->prefix('Rp')
                            ->numeric(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Quotation::STATUS_ON_QUOTE => 'warning',
                        Quotation::STATUS_SENT => 'info',
                        Quotation::STATUS_ACCEPTED => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('change_status')
                    ->label('Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Select::make('status')
                            ->options(Quotation::getStatusOptions())
                            ->required()
                            ->live(),
                        Checkbox::make('create_invoice')
                            ->label('Create Invoice from this Quotation')
                            ->visible(fn ($get) => $get('status') === Quotation::STATUS_ACCEPTED),
                    ])
                    ->action(function (array $data, Quotation $record) {
                        $record->update(['status' => $data['status']]);

                        if ($data['status'] === Quotation::STATUS_ACCEPTED && ($data['create_invoice'] ?? false)) {
                            // Create Invoice
                            $invoice = Invoice::create([
                                'quotation_id' => $record->id,
                                'customer_id' => $record->customer_id,
                                'date' => now(),
                                'due_date' => now()->addDays(7), // Default due date
                                'status' => Invoice::STATUS_SENT,
                                'subtotal' => $record->subtotal,
                                'tax' => $record->tax,
                                'total' => $record->total,
                                'notes' => $record->notes,
                            ]);

                            // Move rentals to invoice (or link them)
                            foreach ($record->rentals as $rental) {
                                $rental->update(['invoice_id' => $invoice->id]);
                            }

                            Notification::make()
                                ->title('Invoice created successfully')
                                ->success()
                                ->send();
                                
                            return redirect()->to(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                        }

                        Notification::make()
                            ->title('Status updated')
                            ->success()
                            ->send();
                    }),
                Action::make('send_quotation')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(function (Quotation $record) {
                        $record->update(['status' => Quotation::STATUS_SENT]);
                        Notification::make()
                            ->title('Quotation sent')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('print_quotation')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (Quotation $record) {
                        $record->load(['customer', 'rentals.items.productUnit.product']);
                        
                        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $record]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Quotation-' . $record->number . '.pdf'
                        );
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RentalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }
}
