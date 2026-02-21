<?php

namespace App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\FinanceAccount;
use App\Models\Account;

class FinanceAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        FinanceAccount::TYPE_BANK => 'Bank Account',
                        FinanceAccount::TYPE_CASH => 'Cash',
                        FinanceAccount::TYPE_EWALLET => 'E-Wallet',
                    ])
                    ->required()
                    ->default(FinanceAccount::TYPE_BANK),
                Select::make('linked_account_id')
                    ->label('Linked GL Account')
                    ->options(Account::query()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => "{$account->code} - {$account->name}"]))
                    ->searchable()
                    ->required(),
                TextInput::make('account_number')
                    ->maxLength(255),
                TextInput::make('holder_name')
                    ->maxLength(255),
                TextInput::make('balance')
                    ->numeric()
                    ->prefix('IDR')
                    ->default(0)
                    ->disabled() // Balance should be managed via transactions
                    ->dehydrated(false), // Do not send to DB on create/edit
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
