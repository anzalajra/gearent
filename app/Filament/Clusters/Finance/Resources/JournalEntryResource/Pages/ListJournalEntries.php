<?php

namespace App\Filament\Clusters\Finance\Resources\JournalEntryResource\Pages;

use App\Filament\Clusters\Finance\Resources\JournalEntryResource;
use App\Models\Account;
use App\Models\FinanceTransaction;
use App\Services\JournalService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

use App\Models\JournalEntry;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_from_simple')
                ->label(fn () => JournalEntry::exists() ? 'Sync' : 'Sync from Simple Finance')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(function () {
                    $unresolvedCategories = JournalService::getUnresolvedCategories();
                    
                    if (empty($unresolvedCategories)) {
                        $unsyncedCount = FinanceTransaction::whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('journal_entries')
                                ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                                ->where('journal_entries.reference_type', FinanceTransaction::class);
                        })->count();

                        if ($unsyncedCount === 0) {
                            return [
                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content('Everything is up to date. All transactions are synced to Journal Entries.'),
                            ];
                        }

                        return [];
                    }

                    $schema = [
                        Placeholder::make('unresolved_info')
                            ->content('The following transaction categories could not be automatically mapped to a Chart of Account. Please select the target account for each category. These selections will be saved as default mappings for future transactions.'),
                    ];

                    foreach ($unresolvedCategories as $category) {
                        $categoryName = $category ?: 'Uncategorized';
                        $hash = md5($category ?? '');
                        $schema[] = Select::make('map_' . $hash)
                            ->label("Map Category: \"$categoryName\"")
                            ->helperText("Select the default COA for '$categoryName'")
                            ->options(Account::query()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => "{$account->code} - {$account->name}"]))
                            ->searchable()
                            ->required();
                    }

                    return $schema;
                })
                ->requiresConfirmation()
                ->modalHeading(fn () => JournalEntry::exists() ? 'Sync Status' : 'Sync Transactions from Simple Finance')
                ->modalDescription(function () {
                    $unsyncedCount = FinanceTransaction::whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('journal_entries')
                            ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                            ->where('journal_entries.reference_type', FinanceTransaction::class);
                    })->count();

                    if ($unsyncedCount === 0) {
                        return null;
                    }

                    $unresolvedCategories = JournalService::getUnresolvedCategories();
                    if (!empty($unresolvedCategories)) {
                        return null; // Description handled by form placeholder
                    }

                    return "Found {$unsyncedCount} transactions from Simple Finance that have not been converted to Journal Entries. Do you want to sync them now?";
                })
                ->modalSubmitActionLabel(function () {
                    $unsyncedCount = FinanceTransaction::whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('journal_entries')
                            ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                            ->where('journal_entries.reference_type', FinanceTransaction::class);
                    })->count();

                    return $unsyncedCount === 0 ? 'Close' : 'Confirm';
                })
                ->action(function (array $data) {
                    $unsyncedCount = FinanceTransaction::whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('journal_entries')
                            ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                            ->where('journal_entries.reference_type', FinanceTransaction::class);
                    })->count();

                    if ($unsyncedCount === 0) {
                        Notification::make()
                            ->title("Everything is up to date")
                            ->success()
                            ->send();
                        return;
                    }

                    // Re-fetch unresolved categories to map hashes back to category names
                    $unresolvedCategories = JournalService::getUnresolvedCategories();
                    $manualMappings = [];
                    
                    foreach ($unresolvedCategories as $category) {
                        $hash = md5($category ?? '');
                        $key = 'map_' . $hash;
                        if (isset($data[$key])) {
                            $manualMappings[$category ?? ''] = $data[$key];
                        }
                    }

                    $count = 0;
                    FinanceTransaction::whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('journal_entries')
                            ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                            ->where('journal_entries.reference_type', FinanceTransaction::class);
                    })->chunk(100, function ($transactions) use (&$count, $manualMappings) {
                        foreach ($transactions as $transaction) {
                            JournalService::syncFromTransaction($transaction, $manualMappings);
                            $count++;
                        }
                    });
                    
                    Notification::make()
                        ->title("Synced {$count} transactions")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
