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
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'user_id')) {
            
            // 1. Drop Foreign Key
            Schema::table('invoices', function (Blueprint $table) {
                // Try multiple common names for the FK
                try {
                    $table->dropForeign(['customer_id']);
                } catch (\Exception $e) {
                    try {
                        $table->dropForeign('invoices_customer_id_foreign');
                    } catch (\Exception $e2) {
                        // ignore
                    }
                }
            });

            // 2. Map customer_id to user_id
            $invoices = DB::table('invoices')->get();
            foreach ($invoices as $invoice) {
                $customer = DB::table('customers')->find($invoice->customer_id);
                if ($customer) {
                    $user = DB::table('users')->where('email', $customer->email)->first();
                    if ($user) {
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['customer_id' => $user->id]);
                    }
                }
            }

            // 3. Rename Column
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('customer_id', 'user_id');
            });

            // 4. Add Foreign Key
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'user_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->renameColumn('user_id', 'customer_id');
                // We cannot easily restore the FK to customers or the original IDs
                // as the mapping is lost.
            });
        }
    }
};
