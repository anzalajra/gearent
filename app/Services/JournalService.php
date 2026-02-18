<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\CategoryMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\FinanceTransaction;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalService
{
    /**
     * Sync a FinanceTransaction to Journal Entry.
     * Creates a Journal Entry if it doesn't exist.
     */
    public static function syncFromTransaction(FinanceTransaction $transaction, array $manualMappings = []): void
    {
        // Check if Journal Entry already exists
        if ($transaction->journalEntry()->exists()) {
            return;
        }

        // 1. Get the Cash/Bank Account (from FinanceAccount)
        $financeAccount = $transaction->account;

        if (!$financeAccount) {
            Log::warning("FinanceTransaction #{$transaction->id} has no FinanceAccount.");
            return;
        }

        if (!$financeAccount->linked_account_id) {
             Log::warning("FinanceTransaction #{$transaction->id}: FinanceAccount #{$financeAccount->id} is not linked to a GL Account.");
             return;
        }
        
        $cashAccountId = $financeAccount->linked_account_id;

        // 2. Get the Contra Account (Revenue/Expense/Liability)
        $contraAccountId = self::resolveContraAccount($transaction, $manualMappings);

        if (!$contraAccountId) {
            Log::warning("FinanceTransaction #{$transaction->id}: Could not determine contra GL account for category '{$transaction->category}'.");
            return;
        }

        // 3. Create Journal Items
        $items = [];
        $amount = $transaction->amount;
        $description = $transaction->description ?: "Transaction #{$transaction->type} - {$transaction->category}";

        if ($transaction->type === FinanceTransaction::TYPE_INCOME || $transaction->type === FinanceTransaction::TYPE_DEPOSIT_IN) {
            // Debit Cash, Credit Income/Liability
            $items[] = ['account_id' => $cashAccountId, 'debit' => $amount, 'credit' => 0];
            $items[] = ['account_id' => $contraAccountId, 'debit' => 0, 'credit' => $amount];
        } elseif ($transaction->type === FinanceTransaction::TYPE_EXPENSE || $transaction->type === FinanceTransaction::TYPE_DEPOSIT_OUT) {
            // Debit Expense/Liability, Credit Cash
            $items[] = ['account_id' => $contraAccountId, 'debit' => $amount, 'credit' => 0];
            $items[] = ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount];
        } elseif ($transaction->type === FinanceTransaction::TYPE_TRANSFER) {
             // For transfer, if we treat it as mapped to another account:
             $items[] = ['account_id' => $contraAccountId, 'debit' => $amount, 'credit' => 0];
             $items[] = ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount];
        }

        if (!empty($items)) {
            self::createEntry($transaction, $description, $items, $transaction->date);
        }
    }

    public static function syncFromInvoice(\App\Models\Invoice $invoice): void
    {
        if ($invoice->journalEntry()->exists()) {
            return;
        }
        
        // Ensure invoice is sent/paid/partial
        if (!in_array($invoice->status, ['sent', 'paid', 'partial'])) {
            return;
        }

        $arAccountId = Setting::get('account_receivable_id');
        $revenueAccountId = Setting::get('rental_revenue_id');
        $taxPayableAccountId = Setting::get('tax_payable_account_id'); // Optional

        if (!$arAccountId || !$revenueAccountId) {
            Log::warning("Invoice #{$invoice->id}: Missing Default AR or Revenue Account in Settings.");
            return;
        }

        $items = [];
        
        // Debit AR (Total)
        $items[] = ['account_id' => $arAccountId, 'debit' => $invoice->total, 'credit' => 0];
        
        // Credit Revenue (Subtotal)
        // Assuming subtotal is net revenue. 
        $revenueAmount = $invoice->subtotal; 
        $items[] = ['account_id' => $revenueAccountId, 'debit' => 0, 'credit' => $revenueAmount];
        
        // Credit Tax Payable
        if ($invoice->tax > 0) {
            if ($taxPayableAccountId) {
                $items[] = ['account_id' => $taxPayableAccountId, 'debit' => 0, 'credit' => $invoice->tax];
            } else {
                // Add to revenue if no tax account
                $items[1]['credit'] += $invoice->tax;
            }
        }

        self::createEntry($invoice, "Invoice #{$invoice->number}", $items, $invoice->date);
    }

    /**
     * Analyze unsynced transactions and return categories that need manual mapping.
     * Returns array of categories.
     */
    public static function getUnresolvedCategories(): array
    {
        // Get categories from FinanceTransactions that don't have a JournalEntry
        $unsyncedCategories = FinanceTransaction::doesntHave('journalEntry')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->toArray();

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
    protected static function isCategoryAutomaticallyResolvable(?string $category): bool
    {
        if (empty($category)) return false;
        
        // Check Category Mapping
        if (CategoryMapping::where('category', $category)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the contra GL account based on transaction category, type, and mappings.
     */
    protected static function resolveContraAccount(FinanceTransaction $transaction, array $manualMappings = []): ?int
    {
        $category = $transaction->category ?? '';
        
        // 1. Check Manual Mappings
        if (isset($manualMappings[$category])) {
            // Persist mapping
            CategoryMapping::updateOrCreate(
                ['category' => $category],
                ['account_id' => $manualMappings[$category]]
            );
            return $manualMappings[$category];
        }

        // 2. Check Database Mappings
        $mapping = CategoryMapping::where('category', $category)->first();
        if ($mapping) {
            return $mapping->account_id;
        }

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
            
            // Recalculate balances
            foreach ($items as $item) {
                if ($item['account_id']) {
                     $account = Account::find($item['account_id']);
                     $account?->recalculateBalance();
                }
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

        return self::createEntry($reference, $description ?? $event, $items);
    }
}
