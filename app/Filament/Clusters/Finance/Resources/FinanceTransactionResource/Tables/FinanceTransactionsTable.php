<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceTransactionResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use App\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Builder;

class FinanceTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('account.name')
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => FinanceTransaction::TYPE_INCOME,
                        'danger' => FinanceTransaction::TYPE_EXPENSE,
                        'warning' => FinanceTransaction::TYPE_TRANSFER,
                    ]),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        FinanceTransaction::TYPE_INCOME => 'Income',
                        FinanceTransaction::TYPE_EXPENSE => 'Expense',
                        FinanceTransaction::TYPE_TRANSFER => 'Transfer',
                    ]),
                SelectFilter::make('finance_account_id')
                    ->relationship('account', 'name')
                    ->label('Account'),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
