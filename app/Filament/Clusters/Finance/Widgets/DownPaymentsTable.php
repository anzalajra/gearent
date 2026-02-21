<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\Rental;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class DownPaymentsTable extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Active Down Payments (Deposit/Uang Muka)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rental::query()
                    ->where('down_payment_amount', '>', 0)
                    ->whereIn('status', [
                        Rental::STATUS_QUOTATION, 
                        Rental::STATUS_CONFIRMED,
                        Rental::STATUS_ACTIVE,
                        Rental::STATUS_LATE_PICKUP,
                        Rental::STATUS_LATE_RETURN,
                    ])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('rental_code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Pickup Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('down_payment_amount')
                    ->label('DP Amount')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('down_payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Rental Status')
                    ->badge(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View Rental')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Rental $record) => route('filament.admin.resources.rentals.view', $record)),
                    
                Action::make('confirm_dp')
                    ->label('Confirm Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $record) => $record->down_payment_status !== 'paid')
                    ->form([
                        Select::make('finance_account_id')
                            ->label('Deposit To Account')
                            ->options(FinanceAccount::where('is_active', true)->pluck('name', 'id'))
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
                        DatePicker::make('date')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (Rental $record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            // Create Finance Transaction
                            $transaction = FinanceTransaction::create([
                                'finance_account_id' => $data['finance_account_id'],
                                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                                'type' => FinanceTransaction::TYPE_INCOME,
                                'amount' => $record->down_payment_amount,
                                'date' => $data['date'],
                                'category' => 'Down Payment',
                                'description' => 'Down Payment for Rental ' . $record->rental_code,
                                'payment_method' => $data['payment_method'],
                                'reference_type' => Rental::class,
                                'reference_id' => $record->id,
                            ]);

                            $record->update(['down_payment_status' => 'paid']);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('DP Marked as Paid & Recorded')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
