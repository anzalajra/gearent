<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions\Action;

class ReceivablesTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Outstanding Invoices (Receivables)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled')
                    ->whereRaw('total > paid_amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Due')
                    ->money('IDR')
                    ->state(fn (Invoice $record): float => $record->total - $record->paid_amount),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Action::make('pay') // Use Table Action, not generic Action
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.edit', $record)),
            ]);
    }
}
