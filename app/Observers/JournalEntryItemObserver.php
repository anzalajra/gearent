<?php

namespace App\Observers;

use App\Models\JournalEntryItem;

class JournalEntryItemObserver
{
    /**
     * Handle the JournalEntryItem "created" event.
     */
    public function created(JournalEntryItem $journalEntryItem): void
    {
        $journalEntryItem->account->recalculateBalance();
    }

    /**
     * Handle the JournalEntryItem "updated" event.
     */
    public function updated(JournalEntryItem $journalEntryItem): void
    {
        if ($journalEntryItem->isDirty(['account_id', 'debit', 'credit'])) {
            $journalEntryItem->account->recalculateBalance();
            
            // If account changed, recalculate old account too
            if ($journalEntryItem->isDirty('account_id')) {
                $originalAccountId = $journalEntryItem->getOriginal('account_id');
                \App\Models\Account::find($originalAccountId)?->recalculateBalance();
            }
        }
    }

    /**
     * Handle the JournalEntryItem "deleted" event.
     */
    public function deleted(JournalEntryItem $journalEntryItem): void
    {
        $journalEntryItem->account->recalculateBalance();
    }

    /**
     * Handle the JournalEntryItem "restored" event.
     */
    public function restored(JournalEntryItem $journalEntryItem): void
    {
        //
    }

    /**
     * Handle the JournalEntryItem "force deleted" event.
     */
    public function forceDeleted(JournalEntryItem $journalEntryItem): void
    {
        //
    }
}
