<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\SaasInvoiceResource\Pages;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SaasInvoiceResource extends Resource
{
    protected static ?string $model = SaasInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Tenant Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Billing & Invoices';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Details')
                ->schema([
                    Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(Tenant::pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->placeholder('Auto-generated if left blank')
                        ->unique(ignoreRecord: true),

                    Select::make('tenant_subscription_id')
                        ->label('Subscription')
                        ->relationship('tenantSubscription', 'id')
                        ->nullable()
                        ->placeholder('Optional'),

                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),

                    Select::make('currency')
                        ->options(['IDR' => 'IDR', 'USD' => 'USD', 'EUR' => 'EUR'])
                        ->default('IDR')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Amounts')
                ->schema([
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->default(0),

                    TextInput::make('tax')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),

                    TextInput::make('total')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->default(0),
                ])
                ->columns(3),

            Section::make('Dates')
                ->schema([
                    DateTimePicker::make('issued_at')
                        ->required()
                        ->default(now()),

                    DateTimePicker::make('due_at')
                        ->required(),

                    DateTimePicker::make('paid_at')
                        ->nullable(),
                ])
                ->columns(3),

            Section::make('Payment')
                ->schema([
                    TextInput::make('payment_method')
                        ->nullable()
                        ->placeholder('e.g., Bank Transfer, Midtrans'),

                    TextInput::make('payment_reference')
                        ->nullable()
                        ->placeholder('Transaction ID or reference'),

                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isDueSoon() ? 'warning' : null),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn () => Tenant::pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaasInvoices::route('/'),
            'create' => Pages\CreateSaasInvoice::route('/create'),
            'edit' => Pages\EditSaasInvoice::route('/{record}/edit'),
        ];
    }
}
