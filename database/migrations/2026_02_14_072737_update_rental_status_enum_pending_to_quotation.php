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
        // 1. Modify enum to include both 'pending' and 'quotation'
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'quotation'");

        // 2. Update existing records
        DB::table('rentals')->where('status', 'pending')->update(['status' => 'quotation']);

        // 3. Modify enum to remove 'pending'
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'quotation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add 'pending' back
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'pending'");

        // 2. Revert data
        DB::table('rentals')->where('status', 'quotation')->update(['status' => 'pending']);

        // 3. Remove 'quotation'
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'pending'");
    }
};
