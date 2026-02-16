<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Widgets\CustomerDepositStats;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Rental;
use App\Services\JournalService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerDeposits extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected string $view = 'filament.clusters.finance.pages.customer-deposits';

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Customer Deposits';
    
    protected static ?string $title = 'Customer Deposits Control';

    protected static ?int $navigationSort = 3;

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerDepositStats::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rental::query()
                    ->where('deposit', '>', 0) // Only rentals that require a deposit
                    ->orWhere('security_deposit_amount', '>', 0) // Or have a held amount
                    ->orderByDesc('created_at')
            )
            ->columns([
                TextColumn::make('rental_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'quotation' => 'gray',
                        'confirmed' => 'info',
                        'active' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'late_pickup' => 'warning',
                        'late_return' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('deposit')
                    ->money('IDR')
                    ->label('Required Deposit')
                    ->sortable(),
                TextColumn::make('security_deposit_amount')
                    ->money('IDR')
                    ->label('Held Amount')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('security_deposit_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'held' => 'success',
                        'refunded' => 'info',
                        'forfeited' => 'danger',
                        'partial_refunded' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('security_deposit_status')
                    ->options([
                        'pending' => 'Pending',
                        'held' => 'Held',
                        'refunded' => 'Refunded',
                        'forfeited' => 'Forfeited',
                    ]),
            ])
            ->actions([
                // Receive Deposit
                Action::make('receive_deposit')
                    ->label('Receive')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Rental $record) => in_array($record->security_deposit_status, ['pending', null]) && $record->deposit > 0)
                    ->form([
                        Select::make('finance_account_id')
                            ->label('Account')
                            ->options(FinanceAccount::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount Received')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Rental $record) => $record->deposit)
                            ->required(),
                        DatePicker::make('transaction_date')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (Rental $record, array $data) {
                        FinanceTransaction::create([
                            'finance_account_id' => $data['finance_account_id'],
                            'type' => FinanceTransaction::TYPE_DEPOSIT_IN,
                            'amount' => $data['amount'],
                            'description' => 'Security Deposit for Rental ' . $record->rental_code,
                            'reference_type' => Rental::class,
                            'reference_id' => $record->id,
                            'date' => $data['transaction_date'],
                        ]);
                        
                        // Auto Journal: Receive Security Deposit
                        JournalService::recordSimpleTransaction(
                            'SECURITY_DEPOSIT_IN', 
                            $record, 
                            $data['amount'], 
                            'Security Deposit for Rental ' . $record->rental_code
                        );

                        $record->update([
                            'security_deposit_status' => 'held',
                            'security_deposit_amount' => $data['amount'],
                        ]);
                        Notification::make()->title('Deposit Received')->success()->send();
                    }),

                // Refund Deposit
                Action::make('refund_deposit')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Rental $record) => $record->security_deposit_status === 'held')
                    ->form([
                        Select::make('finance_account_id')
                            ->label('Account')
                            ->options(FinanceAccount::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Rental $record) => $record->security_deposit_amount > 0 ? $record->security_deposit_amount : $record->deposit)
                            ->required(),
                        TextInput::make('deduction')
                            ->label('Deduction (Damage/Late)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        DatePicker::make('transaction_date')
                            ->default(now())
                            ->required(),
                        Textarea::make('notes')
                            ->label('Refund Notes'),
                    ])
                    ->action(function (Rental $record, array $data) {
                        $deduction = $data['deduction'] ?? 0;
                        $refundAmount = $data['amount'];
                        $transactionDate = $data['transaction_date'];

                        // Outgoing Refund
                        if ($refundAmount > 0) {
                            FinanceTransaction::create([
                                'finance_account_id' => $data['finance_account_id'],
                                'type' => FinanceTransaction::TYPE_DEPOSIT_OUT,
                                'amount' => $refundAmount,
                                'description' => 'Deposit Refund: ' . $record->rental_code,
                                'reference_type' => Rental::class,
                                'reference_id' => $record->id,
                                'date' => $transactionDate,
                            ]);
                        }

                        // Record Deduction as Income if any
                        if ($deduction > 0) {
                            FinanceTransaction::create([
                                'finance_account_id' => $data['finance_account_id'],
                                'type' => FinanceTransaction::TYPE_INCOME,
                                'amount' => $deduction,
                                'description' => 'Deposit Deduction: ' . $record->rental_code,
                                'reference_type' => Rental::class,
                                'reference_id' => $record->id,
                                'date' => $transactionDate,
                            ]);
                        }

                        $record->update([
                            'security_deposit_status' => 'refunded',
                            'security_deposit_amount' => 0, // Reset held amount
                        ]);
                        Notification::make()->title('Deposit Refunded')->success()->send();
                    }),
            ]);
    }
}
