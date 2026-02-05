<?php

namespace App\Filament\Resources\Discounts;

use App\Models\Discount;
use BackedEnum;
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
use UnitEnum;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    // Navigation Configuration
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    
    protected static string|UnitEnum|null $navigationGroup = 'Sales';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Discounts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Discount Information')
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->formatStateUsing(fn ($state) => strtoupper($state))
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->options(Discount::getTypeOptions())
                        ->required()
                        ->default('percentage'),

                    TextInput::make('value')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(2),

            Section::make('Limits')
                ->schema([
                    TextInput::make('min_rental_amount')
                        ->label('Minimum Rental Amount')
                        ->numeric()
                        ->prefix('Rp'),

                    TextInput::make('max_discount_amount')
                        ->label('Maximum Discount Amount')
                        ->numeric()
                        ->prefix('Rp'),

                    TextInput::make('usage_limit')
                        ->label('Total Usage Limit')
                        ->numeric()
                        ->minValue(1),

                    TextInput::make('per_customer_limit')
                        ->label('Per Customer Limit')
                        ->numeric()
                        ->minValue(1),
                ])
                ->columns(2),

            Section::make('Validity')
                ->schema([
                    DatePicker::make('start_date'),
                    DatePicker::make('end_date'),
                    Checkbox::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'percentage' ? 'info' : 'success'),

                TextColumn::make('value')
                    ->formatStateUsing(fn (Discount $record) => $record->type === 'percentage' 
                        ? $record->value . '%' 
                        : 'Rp ' . number_format($record->value, 0, ',', '.')),

                TextColumn::make('usage')
                    ->getStateUsing(fn (Discount $record) => $record->usage_count . ($record->usage_limit ? '/' . $record->usage_limit : '')),

                TextColumn::make('end_date')
                    ->label('Valid Until')
                    ->date()
                    ->color(fn (Discount $record) => $record->end_date?->isPast() ? 'danger' : null),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Discount::getTypeOptions()),
                SelectFilter::make('is_active')
                    ->options(['1' => 'Active', '0' => 'Inactive']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Discounts\Pages\ListDiscounts::route('/'),
            'create' => \App\Filament\Resources\Discounts\Pages\CreateDiscount::route('/create'),
            'edit' => \App\Filament\Resources\Discounts\Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}