<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\AccountMappingResource\Pages;
use App\Models\AccountMapping;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;

class AccountMappingResource extends Resource
{
    protected static ?string $model = AccountMapping::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Journal Mappings';
    
    protected static ?string $modelLabel = 'Mapping';
    
    protected static ?string $pluralModelLabel = 'Journal Mappings';
    
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return Setting::get('finance_mode', 'advanced') === 'advanced';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('event')
                    ->options([
                        'INVOICE_CREATED' => 'Invoice Created (Receivable)',
                        'INVOICE_CREATED_REVENUE' => 'Invoice Created (Revenue)',
                        'INVOICE_CREATED_TAX' => 'Invoice Created (Tax)',
                        'RECEIVE_PAYMENT' => 'Payment Received (Cash/Bank)',
                        'SECURITY_DEPOSIT_IN' => 'Security Deposit Received (Cash)',
                        'SECURITY_DEPOSIT_REFUND' => 'Security Deposit Refunded',
                        'EXPENSE_RECORDED' => 'Expense Recorded',
                    ])
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('event')
                            ->required()
                            ->label('New Event Name'),
                    ])
                    ->createOptionUsing(fn ($data) => $data['event']),
                Select::make('role')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                    ])
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')->sortable()->searchable(),
                TextColumn::make('role')->badge()->color(fn (string $state): string => match ($state) {
                    'debit' => 'info',
                    'credit' => 'warning',
                }),
                TextColumn::make('account.code')->label('Code')->sortable(),
                TextColumn::make('account.name')->label('Account Name')->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event'),
                Tables\Filters\SelectFilter::make('role'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountMappings::route('/'),
            'create' => Pages\CreateAccountMapping::route('/create'),
            'edit' => Pages\EditAccountMapping::route('/{record}/edit'),
        ];
    }
}
