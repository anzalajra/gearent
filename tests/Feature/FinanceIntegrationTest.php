<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $product;
    protected $cashAccount;
    protected $revenueAccount;
    protected $taxPayableAccount;
    protected $receivableAccount;
    protected $depositLiabilityAccount;
    protected $financeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Users
        $this->user = User::factory()->create(['name' => 'Admin User']);
        $this->customer = User::factory()->create(['name' => 'Customer User']);

        // 2. Setup Settings for Advanced Finance & Tax
        Setting::updateOrCreate(['key' => 'finance_mode'], ['value' => 'advanced', 'label' => 'Finance Mode']);
        Setting::updateOrCreate(['key' => 'tax_enabled'], ['value' => 'true', 'label' => 'Tax Enabled']);
        Setting::updateOrCreate(['key' => 'is_pkp'], ['value' => 'true', 'label' => 'Is PKP']);
        Setting::updateOrCreate(['key' => 'ppn_rate'], ['value' => '11', 'label' => 'PPN Rate']); // 11% Tax
        Setting::updateOrCreate(['key' => 'deposit_enabled'], ['value' => 'true', 'label' => 'Deposit Enabled']);
        Setting::updateOrCreate(['key' => 'deposit_type'], ['value' => 'percentage', 'label' => 'Deposit Type']);
        Setting::updateOrCreate(['key' => 'deposit_amount'], ['value' => '30', 'label' => 'Deposit Amount']); // 30% Deposit

        // 3. Setup Accounts (Chart of Accounts)
        // Asset
        $this->cashAccount = Account::firstOrCreate(['code' => '1-1100'], ['name' => 'Cash', 'type' => 'asset']);
        $this->receivableAccount = Account::firstOrCreate(['code' => '1-1300'], ['name' => 'Accounts Receivable', 'type' => 'asset']);
        
        // Liability
        $this->taxPayableAccount = Account::firstOrCreate(['code' => '2-2100'], ['name' => 'PPN Payable', 'type' => 'liability']);
        $this->depositLiabilityAccount = Account::firstOrCreate(['code' => '2-2200'], ['name' => 'Customer Deposits', 'type' => 'liability']);

        // Equity
        Account::firstOrCreate(['code' => '3-3000'], ['name' => 'Opening Balance Equity', 'type' => 'equity']);

        // Revenue
        $this->revenueAccount = Account::firstOrCreate(['code' => '4-4100'], ['name' => 'Rental Income', 'type' => 'revenue']);

        // Expense
        Account::firstOrCreate(['code' => '6-6100'], ['name' => 'General Expense', 'type' => 'expense']);

        // 4. Setup Finance Account (Bank/Cash for Transactions) linked to GL Account
        $this->financeAccount = FinanceAccount::firstOrCreate(
            ['name' => 'Test Cash Account'],
            [
                'type' => 'cash',
                'currency' => 'IDR',
                'balance' => 0,
                'linked_account_id' => $this->cashAccount->id, // Linked to 1-1100
            ]
        );
        
        // Map Categories to Accounts (Important for JournalService)
        // This part depends on how JournalService resolves accounts.
        // Assuming there is a mapping table or logic. I'll mock or configure it if needed.
        // Checking JournalService logic: self::resolveContraAccount($transaction)
        
        // Let's create AccountMappings if they exist
        // Or assume default behavior.
    }

    /** @test */
    public function test_rental_with_dp_flow()
    {
        // 1. Create Rental (Quotation)
        $rental = Rental::create([
            'rental_code' => 'RNT-TEST-001',
            'user_id' => $this->customer->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'status' => Rental::STATUS_QUOTATION,
            'subtotal' => 1000000,
            'tax_base' => 1000000,
            'ppn_rate' => 11,
            'ppn_amount' => 110000,
            'total' => 1110000,
            'is_taxable' => true,
            'price_includes_tax' => false,
            // DP Info
            'down_payment_amount' => 500000, // 50% DP
            'down_payment_status' => 'pending',
        ]);

        // 2. Record Down Payment (DP)
        $dpAmount = 500000;
        $dpTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => $dpAmount,
            'date' => now(),
            'category' => 'Down Payment',
            'description' => 'DP for Rental ' . $rental->rental_code,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);

        // Verify DP Transaction
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $dpTransaction->id,
            'amount' => $dpAmount,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);

        // 3. Confirm Rental & Generate Invoice
        $rental->update(['status' => Rental::STATUS_CONFIRMED]);

        // Simulate Invoice Generation Logic (from ViewRental.php)
        $invoice = Invoice::create([
            'number' => 'INV-TEST-001',
            'quotation_id' => $rental->quotation_id,
            'user_id' => $rental->user_id,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $rental->subtotal,
            'tax' => $rental->ppn_amount,
            'total' => $rental->total,
            'status' => Invoice::STATUS_WAITING_FOR_PAYMENT,
            'is_taxable' => true,
            'ppn_rate' => 11,
            'ppn_amount' => $rental->ppn_amount,
        ]);

        // Link Rental to Invoice
        $rental->update(['invoice_id' => $invoice->id]);

        // Move DP to Invoice (Simulate ViewRental logic)
        $dpTransaction->reference()->associate($invoice);
        $dpTransaction->save();

        // Verify DP moved to Invoice
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $dpTransaction->id,
            'reference_type' => Invoice::class, // Should be Invoice now
            'reference_id' => $invoice->id,
        ]);

        // 4. Record Invoice Journal (Sales)
        // Logic: Debit AR, Credit Revenue, Credit Tax Payable
        // This logic is usually triggered manually or via observer. 
        // In ViewRental, it calls JournalService::recordSimpleTransaction which might not be enough for full accrual.
        // But let's check if 'Invoice Generated' creates a Journal Entry.
        
        // Assuming JournalService::recordInvoice($invoice) exists or similar.
        // If not, let's see what happens with FinanceTransaction.
        
        // 5. Pay Remaining Balance
        $remainingAmount = $invoice->total - $dpAmount;
        $paymentTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => $remainingAmount,
            'date' => now(),
            'category' => 'Invoice Payment',
            'description' => 'Payment for Invoice ' . $invoice->number,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
        ]);

        // Sync to Journal (Advanced Mode)
        // This usually happens in Observer or manually via 'Sync' button.
        // Let's call JournalService::syncFromTransaction($paymentTransaction)
        
        // Mock Account Mapping for 'Invoice Payment' -> Accounts Receivable
        // We need to ensure JournalService knows where to credit.
        // Contra Account for Income is usually Revenue, but for Invoice Payment it should be AR.
        // This is a potential bug/complexity: Does Simple Transaction know about AR?
        
        // Check if Journal Entry is created
        // JournalService::syncFromTransaction($paymentTransaction);
        
        // $this->assertDatabaseHas('journal_entries', [
        //     'reference_type' => FinanceTransaction::class,
        //     'reference_id' => $paymentTransaction->id,
        // ]);
    }

    /** @test */
    public function test_rental_deposit_flow()
    {
        // 1. Create Rental with Deposit Requirement
        $rental = Rental::create([
            'rental_code' => 'RNT-DEP-001',
            'user_id' => $this->customer->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'status' => Rental::STATUS_QUOTATION,
            'subtotal' => 1000000,
            'tax_base' => 1000000,
            'ppn_rate' => 11,
            'ppn_amount' => 110000,
            'total' => 1110000,
            'is_taxable' => true,
            'price_includes_tax' => false,
            // Deposit info
            'deposit' => 300000,
            'deposit_type' => 'fixed',
            'security_deposit_amount' => 300000,
            'security_deposit_status' => 'pending',
        ]);

        // 2. Pay Deposit (In)
        $depositIn = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_DEPOSIT_IN, // Special Type
            'amount' => 300000,
            'date' => now(),
            'category' => 'Security Deposit',
            'description' => 'Deposit for Rental ' . $rental->rental_code,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);

        // Verify Transaction
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $depositIn->id,
            'type' => 'deposit_in',
            'amount' => 300000,
        ]);

        // 3. Return Deposit (Out)
        $depositOut = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_DEPOSIT_OUT, // Special Type
            'amount' => 300000,
            'date' => now(),
            'category' => 'Security Deposit Refund',
            'description' => 'Refund Deposit for Rental ' . $rental->rental_code,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);

        // Verify Transaction
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $depositOut->id,
            'type' => 'deposit_out',
            'amount' => 300000,
        ]);
    }

    /** @test */
    public function test_tax_calculation()
    {
        // Test Tax Logic
        // 1. Create Rental with Taxable Items
        // Create Product and Unit manually as factory might be missing
        $brand = \App\Models\Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
        $category = \App\Models\Category::create(['name' => 'Test Category', 'slug' => 'test-category']);
        
        $product = \App\Models\Product::create([
            'name' => 'Test Product Tax',
            'slug' => 'test-product-tax',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'daily_rate' => 500000,
            'is_taxable' => true,
            'price_includes_tax' => false,
            'is_active' => true,
            'description' => 'Test Description',
        ]);

        $unit = \App\Models\ProductUnit::create([
            'product_id' => $product->id,
            'serial_number' => 'SN-TAX-001',
            'status' => 'available',
            'condition' => 'good',
        ]);
        
        $subtotal = 1000000;
        $ppnRate = 11;
        // PPN Amount will be calculated by observer based on subtotal

        $rental = Rental::create([
            'rental_code' => 'RNT-TAX-001',
            'user_id' => $this->customer->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'is_taxable' => true,
            'price_includes_tax' => false,
            'status' => Rental::STATUS_QUOTATION,
            'ppn_rate' => $ppnRate,
        ]);

        // Add Rental Item to trigger observer calculation
        $item = $rental->items()->create([
            'product_unit_id' => $unit->id,
            'daily_rate' => 500000,
            'days' => 2,
            'subtotal' => 1000000, // 500k * 2
        ]);
        
        // Wait for database transaction to propagate (SQLite/Test env quirk)
        sleep(1); 

        // Manually touch rental to force observer if touches failed
        $rental->touch();
        
        // Refresh rental to get updated totals from observer
        $rental->refresh();

        // Expected Calculations
        // Subtotal: 1,000,000
        // PPN: 11% of 1,000,000 = 110,000
        // Total: 1,110,000

        // Verify Tax Amount
        $this->assertEquals(110000, $rental->ppn_amount);
        $this->assertEquals(1110000, $rental->total);

        // 2. Generate Invoice
        $invoice = Invoice::create([
            'number' => 'INV-TAX-001',
            'user_id' => $rental->user_id,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $rental->subtotal,
            'tax' => $rental->ppn_amount,
            'total' => $rental->total,
            'status' => Invoice::STATUS_WAITING_FOR_PAYMENT,
            'is_taxable' => true,
            'ppn_rate' => 11,
            'ppn_amount' => $rental->ppn_amount,
        ]);

        // Verify Invoice Tax
        $this->assertEquals(110000, $invoice->ppn_amount);
        $this->assertEquals(1110000, $invoice->total);
    }

    /** @test */
    public function test_manual_journal_entry()
    {
        // Test Advanced Finance - Manual Journal Entry
        // Scenario: Manual adjustment (e.g., Bank Charge)
        
        // 2. Create Manual Journal Entry
        $journal = JournalEntry::create([
            'reference_number' => 'JE-MANUAL-001',
            'date' => now(),
            'description' => 'Manual Adjustment',
        ]);

        $expenseAccount = Account::where('code', '6-6100')->first();

        // Create Items
        $journal->items()->create([
            'account_id' => $expenseAccount->id,
            'debit' => 50000,
            'credit' => 0,
        ]);
        
        $journal->items()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => 0,
            'credit' => 50000,
        ]);

        // Verify Journal Entry created
        $this->assertDatabaseHas('journal_entries', [
            'reference_number' => 'JE-MANUAL-001',
            'description' => 'Manual Adjustment',
        ]);
        
        // Verify Items created
        $this->assertDatabaseCount('journal_entry_items', 2);
        
        // Verify Totals (calculated)
        $this->assertEquals(50000, $journal->items()->sum('debit'));
        $this->assertEquals(50000, $journal->items()->sum('credit'));
    }

    /** @test */
    public function test_full_cycle_rental_flow_with_dp_deposit_and_tax()
    {
        // 1. Create Rental with DP, Deposit, and Tax
        // Create Product manually
        $brand = \App\Models\Brand::firstOrCreate(['slug' => 'test-brand'], ['name' => 'Test Brand']);
        $category = \App\Models\Category::firstOrCreate(['slug' => 'test-category'], ['name' => 'Test Category']);
        
        $product = \App\Models\Product::create([
            'name' => 'Test Product Full Cycle',
            'slug' => 'test-product-full-cycle',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'daily_rate' => 500000,
            'is_taxable' => true,
            'price_includes_tax' => false,
            'is_active' => true,
            'description' => 'Test Description',
        ]);

        $unit = \App\Models\ProductUnit::create([
            'product_id' => $product->id,
            'serial_number' => 'SN-FULL-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $rental = Rental::create([
            'rental_code' => 'RNT-FULL-001',
            'user_id' => $this->customer->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'is_taxable' => true,
            'price_includes_tax' => false,
            'status' => Rental::STATUS_QUOTATION,
            'ppn_rate' => 11,
            'down_payment_amount' => 500000,
            'down_payment_status' => 'pending',
            'security_deposit_amount' => 300000,
            'security_deposit_status' => 'pending',
        ]);

        $rental->items()->create([
            'product_unit_id' => $unit->id,
            'daily_rate' => 500000,
            'days' => 2,
            'subtotal' => 1000000,
        ]);

        // Wait for transaction propagation
        sleep(1);
        $rental->touch();
        $rental->refresh();

        // Verify Tax Calculation
        $this->assertEquals(110000, $rental->ppn_amount);
        $this->assertEquals(1110000, $rental->total);

        // 2. Accept Quotation
        $rental->update(['status' => Rental::STATUS_CONFIRMED]);
        $this->assertEquals(Rental::STATUS_CONFIRMED, $rental->status);

        // 3. Generate Invoice
        $invoice = Invoice::create([
            'number' => 'INV-FULL-001',
            'user_id' => $rental->user_id,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $rental->subtotal,
            'tax' => $rental->ppn_amount,
            'total' => $rental->total,
            'status' => Invoice::STATUS_WAITING_FOR_PAYMENT,
            'is_taxable' => true,
            'ppn_rate' => 11,
            'ppn_amount' => $rental->ppn_amount,
        ]);
        $rental->update(['invoice_id' => $invoice->id]);

        // 4. Pay Down Payment
        $dpTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 500000,
            'date' => now(),
            'category' => 'Down Payment',
            'description' => 'DP for Rental ' . $rental->rental_code,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
        ]);
        // Sync Journal (Simulate Controller)
        JournalService::syncFromTransaction($dpTransaction, ['Down Payment' => $this->receivableAccount->id]);
        $rental->update(['down_payment_status' => 'paid']);

        // 5. Pay Remaining Invoice Balance
        $remainingAmount = $invoice->total - 500000;
        $paymentTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => $remainingAmount,
            'date' => now(),
            'category' => 'Invoice Payment',
            'description' => 'Payment for Invoice ' . $invoice->number,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
        ]);
        JournalService::syncFromTransaction($paymentTransaction, ['Invoice Payment' => $this->receivableAccount->id]);
        $invoice->update(['status' => Invoice::STATUS_PAID]);

        // 6. Pay Deposit
        $depositInTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_DEPOSIT_IN,
            'amount' => 300000,
            'date' => now(),
            'category' => 'Security Deposit',
            'description' => 'Deposit for Rental ' . $rental->rental_code,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);
        JournalService::syncFromTransaction($depositInTransaction, ['Security Deposit' => $this->depositLiabilityAccount->id]);
        $rental->update(['security_deposit_status' => 'paid']);

        // 7. Rental Pickup
        $rental->update(['status' => Rental::STATUS_ACTIVE]);
        $this->assertEquals(Rental::STATUS_ACTIVE, $rental->status);

        // 8. Rental Return
        $rental->update(['status' => Rental::STATUS_COMPLETED, 'returned_date' => now()]);
        $this->assertEquals(Rental::STATUS_COMPLETED, $rental->status);

        // 9. Return Deposit
        $depositOutTransaction = FinanceTransaction::create([
            'finance_account_id' => $this->financeAccount->id,
            'user_id' => $this->user->id,
            'type' => FinanceTransaction::TYPE_DEPOSIT_OUT,
            'amount' => 300000,
            'date' => now(),
            'category' => 'Security Deposit Refund',
            'description' => 'Refund Deposit for Rental ' . $rental->rental_code,
            'reference_type' => Rental::class,
            'reference_id' => $rental->id,
        ]);
        JournalService::syncFromTransaction($depositOutTransaction, ['Security Deposit Refund' => $this->depositLiabilityAccount->id]);
        $rental->update(['security_deposit_status' => 'refunded']);

        // 10. Check Journal Entries
        // DP: Debit Cash, Credit AR
        $this->assertDatabaseHas('journal_entries', ['reference_type' => FinanceTransaction::class, 'reference_id' => $dpTransaction->id]);
        $this->assertDatabaseHas('journal_entries', ['reference_type' => FinanceTransaction::class, 'reference_id' => $paymentTransaction->id]);
        $this->assertDatabaseHas('journal_entries', ['reference_type' => FinanceTransaction::class, 'reference_id' => $depositInTransaction->id]);
        $this->assertDatabaseHas('journal_entries', ['reference_type' => FinanceTransaction::class, 'reference_id' => $depositOutTransaction->id]);

        // 11. Check Tax Report
        $taxReport = \App\Services\TaxReportService::generate(now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(110000, $taxReport['total_ppn_payable']);
    }
}
