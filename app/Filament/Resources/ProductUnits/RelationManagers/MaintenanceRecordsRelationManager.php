<?php

namespace App\Filament\Resources\ProductUnits\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaintenanceRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceRecords';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('technician_id')
                    ->relationship('technician', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('cost')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ])
                    ->default('pending')
                    ->required(),

                Section::make('Finance Integration')
                    ->schema([
                        Forms\Components\Toggle::make('create_transaction')
                            ->label('Log as Expense')
                            ->helperText('Automatically create an expense transaction in Finance.')
                            ->default(false)
                            ->reactive(),
                        
                        Forms\Components\Select::make('finance_account_id')
                            ->label('Finance Account')
                            ->options(\App\Models\FinanceAccount::pluck('name', 'id'))
                            ->required(fn ($get) => $get('create_transaction'))
                            ->visible(fn ($get) => $get('create_transaction')),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Technician')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record, array $data) {
                        if (!empty($data['create_transaction']) && !empty($data['finance_account_id']) && $record->cost > 0) {
                            \App\Models\FinanceTransaction::create([
                                'finance_account_id' => $data['finance_account_id'],
                                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                                'type' => \App\Models\FinanceTransaction::TYPE_EXPENSE,
                                'amount' => $record->cost,
                                'date' => $record->date ?? now(),
                                'category' => 'Maintenance',
                                'description' => "Maintenance Cost for Unit: " . $record->productUnit->serial_number . " - " . $record->title,
                                'reference_type' => get_class($record),
                                'reference_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
