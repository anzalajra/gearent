<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Tenant Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->alphaDash(),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price_monthly')
                            ->label('Monthly Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),

                        TextInput::make('price_yearly')
                            ->label('Yearly Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),

                        Select::make('currency')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                            ])
                            ->default('IDR')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Limits')
                    ->schema([
                        TextInput::make('max_users')
                            ->label('Max Users')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('Maximum number of users allowed'),

                        TextInput::make('max_products')
                            ->label('Max Products')
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->required()
                            ->helperText('Maximum number of products'),

                        TextInput::make('max_storage_mb')
                            ->label('Max Storage (MB)')
                            ->numeric()
                            ->default(1024)
                            ->minValue(100)
                            ->required()
                            ->helperText('Storage limit in megabytes'),

                        TextInput::make('max_domains')
                            ->label('Max Domains')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('Maximum custom domains'),
                        
                        TextInput::make('max_rental_transactions_per_month')
                            ->label('Max Rental Transactions / Month')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Kosongkan untuk unlimited (biasanya hanya diisi untuk paket Free).'),
                    ])
                    ->columns(4),

                Section::make('Features')
                    ->schema([
                        Repeater::make('features')
                            ->schema([
                                TextInput::make('feature')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->addActionLabel('Add Feature')
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['feature'] ?? null),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive plans cannot be selected by new tenants'),

                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false)
                            ->helperText('Featured plans are highlighted on the pricing page'),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Monthly')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Yearly')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_users')
                    ->label('Users')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_products')
                    ->label('Products')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('storage_limit')
                    ->label('Storage')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_rental_transactions_per_month')
                    ->label('Rental / Month')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state === null ? 'Unlimited' : (string) $state),

                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->counts('tenants')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
