<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Payment Gateways';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gateway Identity')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., Duitku'),

                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->alphaDash()
                        ->placeholder('e.g., duitku')
                        ->helperText('Kode unik untuk identifikasi gateway di sistem'),
                ])
                ->columns(2),

            Section::make('Credentials')
                ->description('API credentials untuk integrasi payment gateway. Data akan dienkripsi.')
                ->schema([
                    TextInput::make('credentials.merchant_code')
                        ->label('Merchant Code')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Merchant Code dari dashboard gateway'),

                    TextInput::make('credentials.api_key')
                        ->label('API Key')
                        ->required()
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->placeholder('API Key / Secret Key'),
                ])
                ->columns(2),

            Section::make('Settings')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(false)
                        ->helperText('Gateway hanya bisa digunakan jika aktif'),

                    Toggle::make('is_sandbox')
                        ->label('Sandbox Mode')
                        ->default(true)
                        ->helperText('Gunakan environment sandbox/testing'),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_methods_count')
                    ->label('Methods')
                    ->counts('paymentMethods')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_sandbox')
                    ->label('Sandbox')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
