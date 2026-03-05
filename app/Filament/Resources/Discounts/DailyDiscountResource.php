<?php

namespace App\Filament\Resources\Discounts;

use App\Enums\TenantFeature;
use App\Filament\Concerns\ChecksTenantFeature;
use App\Models\DailyDiscount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DailyDiscountResource extends Resource
{
    use ChecksTenantFeature;

    protected static ?string $model = DailyDiscount::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'daily-discounts';

    protected static ?string $modelLabel = 'Daily Discount';

    protected static ?string $pluralModelLabel = 'Daily Discounts';

    public static function canAccess(): bool
    {
        return static::tenantHasFeature(TenantFeature::Promotion);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Daily Discount Information')
                ->description('Contoh: Sewa 3 hari bayar 2 hari')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Sewa 3 Bayar 2'),

                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('Deskripsi promosi...'),

                    TextInput::make('min_days')
                        ->label('Minimum Hari Sewa')
                        ->required()
                        ->numeric()
                        ->minValue(2)
                        ->helperText('Jumlah hari minimum untuk mendapat diskon'),

                    TextInput::make('free_days')
                        ->label('Hari Gratis')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Jumlah hari yang digratiskan'),

                    TextInput::make('max_discount_amount')
                        ->label('Maksimum Diskon')
                        ->numeric()
                        ->prefix('Rp')
                        ->helperText('Kosongkan jika tidak ada batas'),

                    TextInput::make('priority')
                        ->label('Prioritas')
                        ->numeric()
                        ->default(0)
                        ->helperText('Prioritas lebih tinggi diutamakan'),
                ])
                ->columns(2),

            Section::make('Validity')
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),
                    DatePicker::make('end_date')
                        ->label('Tanggal Berakhir'),
                    Checkbox::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('min_days')
                    ->label('Min. Hari')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('free_days')
                    ->label('Hari Gratis')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('max_discount_amount')
                    ->label('Maks. Diskon')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp '.number_format($state, 0, ',', '.') : '-')
                    ->toggleable(),

                TextColumn::make('end_date')
                    ->label('Berlaku Sampai')
                    ->date()
                    ->color(fn (DailyDiscount $record) => $record->end_date?->isPast() ? 'danger' : null)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->options(['1' => 'Aktif', '0' => 'Nonaktif']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Discounts\Pages\ListDailyDiscounts::route('/'),
            'create' => \App\Filament\Resources\Discounts\Pages\CreateDailyDiscount::route('/create'),
            'edit' => \App\Filament\Resources\Discounts\Pages\EditDailyDiscount::route('/{record}/edit'),
        ];
    }
}
