<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\CreateFinanceAccount;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\EditFinanceAccount;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\ListFinanceAccounts;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\ViewFinanceAccount;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\ManageAccountLedger;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Schemas\FinanceAccountForm;
use App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Tables\FinanceAccountsTable;
use App\Models\FinanceAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FinanceAccountResource extends Resource
{
    // Finance Account Resource
    protected static ?string $model = FinanceAccount::class;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Cash & Bank';
    protected static ?string $modelLabel = 'Cash & Bank';
    protected static ?string $slug = 'cash-and-bank';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FinanceAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceAccountsTable::configure($table);
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
            'index' => ListFinanceAccounts::route('/'),
            'create' => CreateFinanceAccount::route('/create'),
            'view' => ViewFinanceAccount::route('/{record}'),
            'edit' => EditFinanceAccount::route('/{record}/edit'),
            'ledger' => ManageAccountLedger::route('/{record}/ledger'),
        ];
    }
}
