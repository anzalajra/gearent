<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Add 'deposit_in' and 'deposit_out' to the enum
            DB::statement("ALTER TABLE finance_transactions MODIFY COLUMN type ENUM('income', 'expense', 'transfer', 'deposit_in', 'deposit_out') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: Reverting this might cause data loss for new types
        DB::statement("ALTER TABLE finance_transactions MODIFY COLUMN type ENUM('income', 'expense', 'transfer') NOT NULL");
    }
};
