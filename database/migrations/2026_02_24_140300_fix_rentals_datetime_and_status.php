<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            // Fix 1: Restore DATETIME for start_date and end_date so time is preserved
            DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATETIME NULL");
            DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATETIME NULL");

            // Fix 2: Add 'partial_return' back to the status ENUM
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return', 'partial_return') NOT NULL DEFAULT 'quotation'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATE NULL");
            DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATE NULL");
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'quotation'");
        }
    }
};
