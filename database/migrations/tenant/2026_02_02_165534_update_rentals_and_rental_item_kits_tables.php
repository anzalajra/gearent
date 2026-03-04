<?php

use App\Helpers\DatabaseHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add cancel_reason to rentals
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('notes');
            }
        });

        // Fix condition_in enum to include lost and broken
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_in',
                ['excellent', 'good', 'fair', 'poor', 'lost', 'broken'],
                null,
                true
            );
        }
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_in',
                ['excellent', 'good', 'fair', 'poor'],
                null,
                true
            );
        }
    }
};