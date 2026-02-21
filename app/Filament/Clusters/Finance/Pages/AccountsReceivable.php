<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Models\Invoice;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AccountsReceivable extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = FinanceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Accounts Receivable';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.clusters.finance.pages.accounts-receivable';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('status', '!=', Invoice::STATUS_PAID)
                    ->whereRaw('total > paid_amount')
            )
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Due')
                    ->money('IDR')
                    ->state(fn (Invoice $record): float => $record->total - $record->paid_amount),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status !== Invoice::STATUS_PAID && $record->status !== 'cancelled' && ($record->total - $record->paid_amount) > 0)
                    ->form(function (Invoice $record) {
                        return [
                            Select::make('finance_account_id')
                                ->label('Deposit To Account')
                                ->options(FinanceAccount::where('is_active', true)->pluck('name', 'id'))
                                ->required(),
                            TextInput::make('amount')
                                ->label('Amount')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->default($record->total - $record->paid_amount)
                                ->maxValue($record->total - $record->paid_amount),
                            DatePicker::make('date')
                                ->label('Payment Date')
                                ->default(now())
                                ->required(),
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'Cash' => 'Cash',
                                    'Transfer' => 'Bank Transfer',
                                    'QRIS' => 'QRIS',
                                    'Credit Card' => 'Credit Card',
                                ])
                                ->required(),
                            Textarea::make('notes')
                                ->label('Notes'),
                        ];
                    })
                    ->action(function (Invoice $record, array $data) {
                        $transaction = new FinanceTransaction([
                            'finance_account_id' => $data['finance_account_id'],
                            'user_id' => Auth::id(),
                            'type' => FinanceTransaction::TYPE_INCOME,
                            'amount' => $data['amount'],
                            'date' => $data['date'],
                            'category' => 'Invoice Payment',
                            'description' => 'Payment for Invoice #' . $record->number,
                            'payment_method' => $data['payment_method'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                        $transaction->reference()->associate($record);
                        $transaction->save();

                        // Recalculate invoice status
                        $record->recalculate();

                        Notification::make()
                            ->title('Payment Recorded')
                            ->success()
                            ->send();
                    }),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.edit', $record)),
            ])
            ->bulkActions([
                //
            ]);
    }
}
