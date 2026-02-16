<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages;

use App\Models\FinanceTransaction;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use App\Models\FinanceAccount;

class ManageAccountLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = 'App\Filament\Clusters\Finance\Resources\FinanceAccountResource';

    protected string $view = 'filament.clusters.finance.resources.finance-account-resource.pages.manage-account-ledger';

    protected static ?string $title = 'General Ledger';

    public FinanceAccount $record;

    public function mount(FinanceAccount $record): void
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FinanceTransaction::query()
                    ->where('finance_account_id', $this->record->id)
            )
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray')
                    ->label('Ref'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => in_array($state, [FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_DEPOSIT_IN]),
                        'danger' => fn ($state) => in_array($state, [FinanceTransaction::TYPE_EXPENSE, FinanceTransaction::TYPE_DEPOSIT_OUT]),
                        'warning' => FinanceTransaction::TYPE_TRANSFER,
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('date', 'desc');
    }
}
