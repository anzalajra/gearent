<?php

namespace App\Observers;

use App\Models\FinanceTransaction;
use App\Models\Account;
use App\Services\JournalService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FinanceTransactionObserver
{
    /**
     * Handle the FinanceTransaction "created" event.
     */
    public function created(FinanceTransaction $transaction): void
    {
        JournalService::syncFromTransaction($transaction);
    }

    /**
     * Handle the FinanceTransaction "updated" event.
     */
    public function updated(FinanceTransaction $financeTransaction): void
    {
        // TODO: Update journal entry? Or void and recreate?
        // For now, complex to handle updates to amounts/accounts.
    }

    /**
     * Handle the FinanceTransaction "deleted" event.
     */
    public function deleted(FinanceTransaction $financeTransaction): void
    {
        // Void the journal entry?
        // Ideally we should soft delete or mark as void.
    }
}
