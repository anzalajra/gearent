<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Finance Accounts (Bank, Cash Drawer, etc.)
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. BCA, Cash Drawer
            $table->string('type')->default('bank'); // bank, cash, e-wallet
            $table->string('account_number')->nullable();
            $table->string('holder_name')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Finance Transactions (Money In/Out)
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Who recorded this transaction
            $table->enum('type', ['income', 'expense', 'transfer', 'deposit_in', 'deposit_out']);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('category')->nullable(); // Rental Payment, Maintenance, Salary, etc.
            $table->string('description')->nullable();
            $table->nullableMorphs('reference'); // For linking to Invoice, Rental, ExpenseRequest
            $table->string('payment_method')->nullable(); // transfer, cash, qris
            $table->string('proof_document')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add paid_amount to invoices for tracking partial payments
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'paid_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_accounts');
        
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'paid_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
