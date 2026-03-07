<?php

namespace App\Filament\Central\Resources;

use App\Enums\PaymentMethodType;
use App\Filament\Central\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Payment Methods';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Method Configuration')
                ->schema([
                    Select::make('payment_gateway_id')
                        ->label('Payment Gateway')
                        ->options(PaymentGateway::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    Select::make('type')
                        ->label('Tipe')
                        ->options(PaymentMethodType::toOptions())
                        ->required(),

                    TextInput::make('channel_code')
                        ->label('Channel Code')
                        ->maxLength(10)
                        ->placeholder('e.g., BC, I1, SP')
                        ->helperText('Kode channel dari gateway (lihat dokumentasi Duitku)'),

                    TextInput::make('display_name')
                        ->label('Nama Tampilan')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., BCA Virtual Account'),

                    TextInput::make('icon')
                        ->label('Icon URL')
                        ->maxLength(500)
                        ->placeholder('URL gambar/icon metode pembayaran'),
                ])
                ->columns(2),

            Section::make('Fee & Settings')
                ->schema([
                    TextInput::make('admin_fee')
                        ->label('Admin Fee')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp'),

                    Select::make('admin_fee_type')
                        ->label('Tipe Fee')
                        ->options([
                            'fixed' => 'Fixed (Rp)',
                            'percentage' => 'Percentage (%)',
                        ])
                        ->default('fixed'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentGateway.name')
                    ->label('Gateway')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethodType ? $state->getLabel() : $state),

                Tables\Columns\TextColumn::make('channel_code')
                    ->label('Code')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('admin_fee')
                    ->label('Fee')
                    ->formatStateUsing(function ($record) {
                        if ((float) $record->admin_fee === 0.0) {
                            return 'Gratis';
                        }

                        return $record->admin_fee_type === 'percentage'
                            ? $record->admin_fee.'%'
                            : 'Rp '.number_format((float) $record->admin_fee, 0, ',', '.');
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
