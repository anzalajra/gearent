<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductUnit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    protected static ?string $title = 'Product Units';

    protected static ?string $recordTitleAttribute = 'serial_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Unit Information')
                    ->columns(2)
                    ->schema([
                        Select::make('product_variation_id')
                            ->relationship('variation', 'name', fn ($query, $livewire) => $query->where('product_id', $livewire->getOwnerRecord()->id))
                            ->label('Variation')
                            ->visible(fn ($livewire) => $livewire->getOwnerRecord()->variations()->exists())
                            ->required(fn ($livewire) => $livewire->getOwnerRecord()->variations()->exists())
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('daily_rate')
                                    ->label('Override Price (Optional)')
                                    ->numeric()
                                    ->prefix('Rp'),
                            ])
                            ->createOptionUsing(function (array $data, $livewire) {
                                return $livewire->getOwnerRecord()->variations()->create($data)->id;
                            })
                            ->columnSpanFull(),

                        TextInput::make('serial_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('SN-A7IV-001'),

                        Select::make('condition')
                            ->options(ProductUnit::getConditionOptions())
                            ->required()
                            ->default('excellent'),

                        Select::make('status')
                            ->options(ProductUnit::getStatusOptions())
                            ->required()
                            ->default('available'),

                        DatePicker::make('purchase_date')
                            ->label('Purchase Date'),

                        TextInput::make('purchase_price')
                            ->label('Purchase Price')
                            ->numeric()
                            ->prefix('Rp'),

                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Kits / Accessories')
                    ->description('List of accessories or extra gear included with this unit')
                    ->schema([
                        Repeater::make('kits')
                            ->relationship('kits')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('serial_number')
                                    ->maxLength(255),
                                Select::make('condition')
                                    ->options(\App\Models\UnitKit::getConditionOptions())
                                    ->required()
                                    ->default('excellent'),
                                Textarea::make('notes')
                                    ->rows(1),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('variation.name')
                    ->label('Variation')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('kits_count')
                    ->counts('kits')
                    ->label('Kits')
                    ->badge()
                    ->color('info'),

                TextColumn::make('kits.name')
                    ->label('Kit List')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('condition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        'broken' => 'danger',
                        'lost' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'scheduled' => 'primary',
                        'rented' => 'warning',
                        'maintenance' => 'info',
                        'retired' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('purchase_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('purchase_price')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
