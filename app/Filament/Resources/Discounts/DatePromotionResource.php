<?php

namespace App\Filament\Resources\Discounts;

use App\Enums\TenantFeature;
use App\Filament\Concerns\ChecksTenantFeature;
use App\Models\DatePromotion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DatePromotionResource extends Resource
{
    use ChecksTenantFeature;

    protected static ?string $model = DatePromotion::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'date-promotions';

    protected static ?string $modelLabel = 'Date Promotion';

    public static function canAccess(): bool
    {
        return static::tenantHasFeature(TenantFeature::Promotion);
    }

    protected static ?string $pluralModelLabel = 'Date Promotions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Date Promotion Information')
                ->description('Promo pada tanggal tertentu (misal: Hari Kemerdekaan, Natal, dll)')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Promo Hari Kemerdekaan'),

                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('Deskripsi promosi...'),

                    Select::make('type')
                        ->label('Tipe Diskon')
                        ->options(DatePromotion::getTypeOptions())
                        ->required()
                        ->default('percentage'),

                    TextInput::make('value')
                        ->label('Nilai Diskon')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Persentase atau nominal'),

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

            Section::make('Tanggal Promo')
                ->schema([
                    DatePicker::make('promo_date')
                        ->label('Tanggal Promo')
                        ->required()
                        ->helperText('Tanggal spesifik untuk promo'),
                    Checkbox::make('recurring_yearly')
                        ->label('Berulang Setiap Tahun')
                        ->helperText('Aktif pada tanggal yang sama setiap tahun'),
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

                TextColumn::make('promo_date')
                    ->label('Tanggal Promo')
                    ->date('d M')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => $state === 'percentage' ? 'info' : 'success'),

                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(fn (DatePromotion $record) => $record->type === 'percentage'
                        ? $record->value.'%'
                        : 'Rp '.number_format($record->value, 0, ',', '.')),

                IconColumn::make('recurring_yearly')
                    ->label('Tahunan')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(DatePromotion::getTypeOptions()),
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
            'index' => \App\Filament\Resources\Discounts\Pages\ListDatePromotions::route('/'),
            'create' => \App\Filament\Resources\Discounts\Pages\CreateDatePromotion::route('/create'),
            'edit' => \App\Filament\Resources\Discounts\Pages\EditDatePromotion::route('/{record}/edit'),
        ];
    }
}
