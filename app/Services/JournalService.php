<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\CategoryMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\FinanceTransaction;
use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalService
{
    protected static $keywordMap = [
        // Income
        'invoice' => 26, // Pendapatan Sewa
        'rental' => 26, // Pendapatan Sewa
        'sewa' => 26, // Pendapatan Sewa
        'sales' => 26, // Pendapatan Sewa
        'penjualan' => 26, // Pendapatan Sewa
        'down payment' => 17, // Pendapatan Diterima Dimuka
        'dp' => 17, // Pendapatan Diterima Dimuka
        'interest' => 41, // Pendapatan Bunga Bank
        'bunga' => 41, // Pendapatan Bunga Bank
        
        // Expense
        'maintenance' => 30, // Beban Perawatan Aset
        'repair' => 30, // Beban Perawatan Aset
        'perawatan' => 30, // Beban Perawatan Aset
        'salary' => 35, // Beban Gaji
        'gaji' => 35, // Beban Gaji
        'listrik' => 33, // Beban Listrik
        'air' => 33, // Beban Air
        'internet' => 33, // Beban Internet
        'utility' => 33, // Beban Utility
        'marketing' => 38, // Beban Pemasaran
        'iklan' => 38, // Beban Pemasaran
        'supplies' => 39, // Beban Perlengkapan
        'perlengkapan' => 39, // Beban Perlengkapan
        'admin' => 43, // Beban Administrasi Bank
        'tax' => 44, // Beban Pajak
        'pajak' => 44, // Beban Pajak
    ];

    /**
     * Sync a FinanceTransaction to Journal Entry.
     * Creates a Journal Entry if it doesn't exist.
     */
    public static function syncFromTransaction(FinanceTransaction $transaction, array $manualMappings = []): void
    {
        // Check if Journal Entry already exists
        $exists = JournalEntry::where('reference_type', FinanceTransaction::class)
            ->where('reference_id', $transaction->id)
            ->exists();

        if ($exists) {
            return;
        }

        // 1. Get the Cash/Bank Account (from FinanceAccount)
        $transaction->load('account');
        $financeAccount = $transaction->account;

        if (!$financeAccount) {
            return;
        }

        if (!$financeAccount->linked_account_id) {
            // Try to auto-link if an account with same name exists
            $linkedAccount = Account::where('name', $financeAccount->name)->first();
            if ($linkedAccount) {
                $financeAccount->update(['linked_account_id' => $linkedAccount->id]);
            } else {
                // Cannot proceed without a linked GL account
                Log::warning("FinanceTransaction #{$transaction->id} sync failed: FinanceAccount #{$financeAccount->id} is not linked to a GL Account.");
                return;
            }
        }
        
        $cashAccountId = $financeAccount->linked_account_id;

        // 2. Get the Contra Account (Revenue/Expense/Liability)
        $contraAccountId = self::resolveContraAccount($transaction, $manualMappings);

        if (!$contraAccountId) {
            Log::warning("FinanceTransaction #{$transaction->id}: Could not determine contra GL account for category '{$transaction->category}' or type '{$transaction->type}'.");
            return;
        }

        // 3. Create Journal Entry
        $items = [];
        $amount = $transaction->amount;

        if ($transaction->type === FinanceTransaction::TYPE_INCOME || $transaction->type === FinanceTransaction::TYPE_DEPOSIT_IN) {
            // Debit Cash, Credit Income/Liability
            $items[] = ['account_id' => $cashAccountId, 'debit' => $amount, 'credit' => 0];
            $items[] = ['account_id' => $contraAccountId, 'debit' => 0, 'credit' => $amount];
        } elseif ($transaction->type === FinanceTransaction::TYPE_EXPENSE || $transaction->type === FinanceTransaction::TYPE_DEPOSIT_OUT) {
            // Debit Expense/Liability, Credit Cash
            $items[] = ['account_id' => $contraAccountId, 'debit' => $amount, 'credit' => 0];
            $items[] = ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount];
        }

        if (!empty($items)) {
            self::createEntry(
                $transaction,
                $transaction->description ?? "Transaction #{$transaction->id} ({$transaction->type})",
                $items,
                $transaction->date
            );
        }
    }

    /**
     * Analyze unsynced transactions and return categories that need manual mapping.
     * Returns array of categories.
     */
    public static function getUnresolvedCategories(): array
    {
        $unsyncedCategories = FinanceTransaction::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('journal_entries')
                ->whereColumn('journal_entries.reference_id', 'finance_transactions.id')
                ->where('journal_entries.reference_type', FinanceTransaction::class);
        })
        ->whereNotNull('category')
        ->where('category', '!=', '')
        ->distinct()
        ->pluck('category');

        $unresolved = [];
        foreach ($unsyncedCategories as $category) {
            if (!self::isCategoryAutomaticallyResolvable($category)) {
                $unresolved[] = $category;
            }
        }

        return $unresolved;
    }

    /**
     * Check if a category can be automatically resolved to an account.
     */
    protected static function isCategoryAutomaticallyResolvable(string $category): bool
    {
        // 0. Check Category Mapping
        if (CategoryMapping::where('category', $category)->exists()) {
            return true;
        }

        // 1. Exact match
        if (Account::where('name', $category)->exists()) {
            return true;
        }

        // 2. Keyword match
        $categoryLower = strtolower($category);
        foreach (self::$keywordMap as $keyword => $id) {
            if (str_contains($categoryLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the contra GL account based on transaction category, type, and mappings.
     */
    protected static function resolveContraAccount(FinanceTransaction $transaction, array $manualMappings = []): ?int
    {
        // 0. Check Manual Mappings (passed from UI)
        if ($transaction->category && isset($manualMappings[$transaction->category])) {
            // Persist this mapping for future use if it doesn't exist
            CategoryMapping::firstOrCreate(
                ['category' => $transaction->category],
                ['account_id' => $manualMappings[$transaction->category]]
            );
            return $manualMappings[$transaction->category];
        }

        // 0.5 Check Persisted Category Mappings
        if ($transaction->category) {
            $mappedId = CategoryMapping::where('category', $transaction->category)->value('account_id');
            if ($mappedId) return $mappedId;
        }

        // 1. Try exact match by Category Name
        if ($transaction->category) {
            $accountId = Account::where('name', $transaction->category)->value('id');
            if ($accountId) return $accountId;
        }

        // 2. Keyword Matching for Common Categories (English/Indonesian)
        if ($transaction->category) {
            $category = strtolower($transaction->category);
            
            foreach (self::$keywordMap as $keyword => $id) {
                if (str_contains($category, $keyword)) {
                    return $id;
                }
            }
        }

        // 3. Fallback to Mappings based on Type - REMOVED to force manual mapping
        /*
        $event = match ($transaction->type) {
            FinanceTransaction::TYPE_INCOME => 'INCOME_DEFAULT',
            FinanceTransaction::TYPE_EXPENSE => 'EXPENSE_DEFAULT',
            FinanceTransaction::TYPE_DEPOSIT_IN => 'SECURITY_DEPOSIT_IN',
            FinanceTransaction::TYPE_DEPOSIT_OUT => 'SECURITY_DEPOSIT_OUT',
            default => null,
        };

        if ($event) {
            $role = ($transaction->type === FinanceTransaction::TYPE_INCOME || $transaction->type === FinanceTransaction::TYPE_DEPOSIT_IN) ? 'credit' : 'debit';
            $mappedId = self::getAccount($event, $role);
            if ($mappedId) return $mappedId;
        }
        */

        // 4. Hard Fallback if Mappings Missing - REMOVED to force manual mapping
        /*
        if ($transaction->type === FinanceTransaction::TYPE_INCOME) {
            return 40; // Pendapatan Lain-lain
        } elseif ($transaction->type === FinanceTransaction::TYPE_EXPENSE) {
            return 42; // Beban Lain-lain
        }
        */

        return null;
    }

    /**
     * Get the account ID for a specific event and role from mappings.
     */
    public static function getAccount(string $event, string $role): ?int
    {
        return AccountMapping::where('event', $event)
            ->where('role', $role)
            ->value('account_id');
    }

    /**
     * Create a journal entry with multiple items.
     * items format: [['account_id' => 1, 'debit' => 100, 'credit' => 0], ...]
     */
    public static function createEntry(Model $reference, string $description, array $items, ?string $date = null): ?JournalEntry
    {
        if (empty($items)) {
            return null;
        }

        // Validate balance
        $totalDebit = collect($items)->sum('debit');
        $totalCredit = collect($items)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            // Log error or throw exception? For now just log and continue or throw
            // throw new \Exception("Journal Entry is not balanced: Debit $totalDebit != Credit $totalCredit");
            // Better to allow it but maybe mark as draft? Or just force balance?
            // Let's just create it but maybe add a warning in description?
            $description .= " [WARNING: Unbalanced D:$totalDebit C:$totalCredit]";
        }

        return DB::transaction(function () use ($reference, $description, $items, $date) {
            $entry = JournalEntry::create([
                'reference_number' => 'JRN-' . date('YmdHis') . '-' . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT),
                'date' => $date ?? now(),
                'description' => $description,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
            ]);

            foreach ($items as $item) {
                if (($item['debit'] ?? 0) == 0 && ($item['credit'] ?? 0) == 0) {
                    continue;
                }
                
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item['account_id'],
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Record a simple transaction where one account is debited and another credited with the same amount.
     */
    public static function recordSimpleTransaction(string $event, Model $reference, float $amount, ?string $description = null): ?JournalEntry
    {
        $debitAccountId = self::getAccount($event, 'debit');
        $creditAccountId = self::getAccount($event, 'credit');

        if (!$debitAccountId || !$creditAccountId) {
            // Missing mapping
            return null;
        }

        $items = [
            [
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ];

        return self::createEntry($reference, $description ?? "Auto-generated for $event", $items);
    }
}
