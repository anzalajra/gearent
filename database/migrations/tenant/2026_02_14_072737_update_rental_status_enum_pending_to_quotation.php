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
        // 1. Modify enum to include both 'pending' and 'quotation'
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return', 'partial_return'],
                'quotation',
                false
            );
        }

        // 2. Update existing records
        DB::table('rentals')->where('status', 'pending')->update(['status' => 'quotation']);

        // 3. Modify enum to remove 'pending'
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return', 'partial_return'],
                'quotation',
                false
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // 1. Add 'pending' back
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'pending',
                false
            );
        }

        // 2. Revert data
        DB::table('rentals')->where('status', 'quotation')->update(['status' => 'pending']);

        if (DB::getDriverName() !== 'sqlite') {
            // 3. Remove 'quotation'
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'pending',
                false
            );
        }
    }
};
