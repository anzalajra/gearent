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
            // Fix Enum: Ensure 'quotation' exists and remove 'pending'/'partial_return'
            // This fixes the critical bug where 'quotation' status was missing in MySQL enum
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'quotation'");

            // Fix Dates: Make nullable to allow draft rentals without dates
            // This fixes the bug where creating a rental without dates throws an error
            DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATE NULL");
            DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATE NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Revert is tricky because we might lose data if we remove 'quotation', but generally we don't want to revert this fix.
            // Keeping it simple for now.
        }
    }
};
