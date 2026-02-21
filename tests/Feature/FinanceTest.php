<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed accounts
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    public function test_journal_entry_is_created_when_finance_transaction_is_created()
    {
        // 1. Setup Accounts
        $cashGL = Account::where('code', '1-1100')->first();
        $revenueGL = Account::where('code', '4-1100')->first();

        // 2. Setup Finance Account linked to GL
        $financeAccount = FinanceAccount::create([
            'name' => 'Test Bank',
            'type' => FinanceAccount::TYPE_BANK,
            'balance' => 0,
            'linked_account_id' => $cashGL->id,
        ]);

        $user = User::factory()->create();

        // 3. Create Income Transaction
        $transaction = FinanceTransaction::create([
            'finance_account_id' => $financeAccount->id,
            'user_id' => $user->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 100000,
            'date' => now(),
            'category' => 'Pendapatan Sewa', // Should match Account name
            'description' => 'Test Income',
        ]);

        // 4. Assert Journal Entry Created
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => FinanceTransaction::class,
            'reference_id' => $transaction->id,
            'description' => 'Test Income',
        ]);

        $entry = JournalEntry::where('reference_id', $transaction->id)->first();
        
        // Assert Items: Debit Cash, Credit Revenue
        $this->assertCount(2, $entry->items);
        
        $debitItem = $entry->items()->where('debit', '>', 0)->first();
        $creditItem = $entry->items()->where('credit', '>', 0)->first();

        $this->assertEquals($cashGL->id, $debitItem->account_id);
        $this->assertEquals(100000, $debitItem->debit);
        
        $this->assertEquals($revenueGL->id, $creditItem->account_id);
        $this->assertEquals(100000, $creditItem->credit);
    }

    public function test_journal_entry_uses_default_mapping_when_category_not_found()
    {
        // 1. Setup Accounts
        $cashGL = Account::where('code', '1-1100')->first();
        // Default expense is 5-1200 per seeder

        // 2. Setup Finance Account
        $financeAccount = FinanceAccount::create([
            'name' => 'Test Cash',
            'type' => FinanceAccount::TYPE_CASH,
            'balance' => 0,
            'linked_account_id' => $cashGL->id,
        ]);

        $user = User::factory()->create();

        // 3. Create Expense Transaction with unknown category
        $transaction = FinanceTransaction::create([
            'finance_account_id' => $financeAccount->id,
            'user_id' => $user->id,
            'type' => FinanceTransaction::TYPE_EXPENSE,
            'amount' => 50000,
            'date' => now(),
            'category' => 'Unknown Expense',
            'description' => 'Test Expense',
        ]);

        // 4. Assert Journal Entry Created
        $this->assertDatabaseHas('journal_entries', [
            'reference_id' => $transaction->id,
        ]);

        $entry = JournalEntry::where('reference_id', $transaction->id)->first();
        
        // Assert Items: Debit Expense (Default), Credit Cash
        $debitItem = $entry->items()->where('debit', '>', 0)->first();
        $creditItem = $entry->items()->where('credit', '>', 0)->first();

        // Check if debit item is the default expense account
        $defaultExpenseGL = Account::where('code', '5-1200')->first();
        $this->assertEquals($defaultExpenseGL->id, $debitItem->account_id);
        
        $this->assertEquals($cashGL->id, $creditItem->account_id);
    }
}
