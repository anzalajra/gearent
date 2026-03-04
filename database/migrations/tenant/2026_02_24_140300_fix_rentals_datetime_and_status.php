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
     * 
     * Fixes two issues:
     * 1. start_date and end_date were reverted to DATE by a previous migration,
     *    causing pickup/return times to be lost. Changed back to DATETIME.
     * 2. 'partial_return' was missing from the status ENUM.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $driver = DB::getDriverName();
            
            // Fix 1: Restore DATETIME for start_date and end_date so time is preserved
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE rentals ALTER COLUMN start_date TYPE TIMESTAMP USING start_date::timestamp");
                DB::statement("ALTER TABLE rentals ALTER COLUMN end_date TYPE TIMESTAMP USING end_date::timestamp");
            } else {
                DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATETIME NULL");
                DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATETIME NULL");
            }

            // Fix 2: Add 'partial_return' back to the status ENUM
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
            $driver = DB::getDriverName();
            
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE rentals ALTER COLUMN start_date TYPE DATE USING start_date::date");
                DB::statement("ALTER TABLE rentals ALTER COLUMN end_date TYPE DATE USING end_date::date");
            } else {
                DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATE NULL");
                DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATE NULL");
            }
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'quotation',
                false
            );
        }
    }
};
