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
        // 1. Chart of Accounts (Daftar Akun)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 1-1100
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('subtype')->nullable(); // current_asset, fixed_asset, etc.
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Journal Entries (Jurnal Umum Header)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); // JRN-202310001
            $table->date('date');
            $table->text('description')->nullable();
            $table->nullableMorphs('reference'); // Link to Invoice, Rental, Payment, etc.
            $table->timestamps();
        });

        // 3. Journal Entry Items (Jurnal Umum Detail)
        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 4. Automatic Journal Mapping (Pengaturan Mapping Akun)
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('event'); // e.g. RECEIVE_RENTAL_PAYMENT
            $table->enum('role', ['debit', 'credit']);
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamps();
            
            // Ensure unique mapping per event+role
            // But sometimes one event might have multiple debits/credits?
            // For simple mapping: 1 debit account, 1 credit account usually.
            // But let's keep it flexible, maybe just index for performance.
            $table->index(['event', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
