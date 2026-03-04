<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Helpers\DatabaseHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Add 'deposit_in' and 'deposit_out' to the enum
            DatabaseHelper::modifyEnumColumn(
                'finance_transactions',
                'type',
                ['income', 'expense', 'transfer', 'deposit_in', 'deposit_out'],
                null,
                false
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: Reverting this might cause data loss for new types
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'finance_transactions',
                'type',
                ['income', 'expense', 'transfer'],
                null,
                false
            );
        }
    }
};
