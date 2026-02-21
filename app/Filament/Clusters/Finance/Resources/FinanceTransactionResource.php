<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages\CreateFinanceTransaction;
use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages\EditFinanceTransaction;
use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Pages\ListFinanceTransactions;
use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Schemas\FinanceTransactionForm;
use App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Tables\FinanceTransactionsTable;
use App\Models\FinanceTransaction;
use App\Models\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FinanceTransactionResource extends Resource
{
    protected static ?string $model = FinanceTransaction::class;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $slug = 'simple-transactions';

    protected static ?string $navigationLabel = 'Journal Entries';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $recordTitleAttribute = 'description';

    public static function shouldRegisterNavigation(): bool
    {
        return Setting::get('finance_mode', 'advanced') === 'simple';
    }

    public static function form(Schema $schema): Schema
    {
        return FinanceTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        // Pages registered in the cluster
        return [
            'index' => ListFinanceTransactions::route('/'),
            'create' => CreateFinanceTransaction::route('/create'),
            'edit' => EditFinanceTransaction::route('/{record}/edit'),
        ];
    }
}
