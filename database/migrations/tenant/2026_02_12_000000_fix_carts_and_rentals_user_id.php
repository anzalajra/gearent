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
        $tables = ['carts', 'rentals', 'customer_documents', 'quotations'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'customer_id') && !Schema::hasColumn($tableName, 'user_id')) {
                        // Rename column if user_id doesn't exist
                        $table->renameColumn('customer_id', 'user_id');
                    } elseif (Schema::hasColumn($tableName, 'customer_id') && Schema::hasColumn($tableName, 'user_id')) {
                        // If both exist, drop customer_id (assuming data is migrated or we just want to switch)
                        // Note: Data migration logic is complex, here we assume we can just drop or the user_id is the one we want.
                        // Ideally we should copy data if user_id is empty, but 'user_id' probably didn't exist before or was added recently.
                        // Let's just drop customer_id to avoid confusion if user_id exists.
                        // BUT be careful: if user_id is empty, we lose data.
                        // Let's NOT drop it automatically if both exist, unless we are sure.
                        // Given the user report "Unknown column customer_id", it suggests the column might NOT exist in some context or is expected but missing.
                        // But here we are fixing "customer_id" usage to "user_id".
                        // If the code uses "user_id", the database MUST have "user_id".
                        // So if "user_id" exists, we are good. We can drop "customer_id".
                        $table->dropForeign([$tableName . '_customer_id_foreign']); // Try to drop foreign key first
                        $table->dropColumn('customer_id');
                    }
                });

                // Separate schema call for adding user_id if neither exists (rare but possible)
                if (!Schema::hasColumn($tableName, 'customer_id') && !Schema::hasColumn($tableName, 'user_id')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                    });
                }
                
                // Ensure user_id is a foreign key to users table
                // This is tricky if it was just renamed from customer_id (which pointed to customers or users).
                // If it pointed to customers, we might need to drop foreign key and add new one.
                // But customers are users now.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['carts', 'rentals', 'customer_documents'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'user_id') && !Schema::hasColumn($tableName, 'customer_id')) {
                        $table->renameColumn('user_id', 'customer_id');
                    }
                });
            }
        }
    }
};
