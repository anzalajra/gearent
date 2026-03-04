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
            // Fix Enum: Ensure 'quotation' exists and remove 'pending'/'partial_return'
            // This fixes the critical bug where 'quotation' status was missing in MySQL enum
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['quotation', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'quotation',
                false
            );

            // Fix Dates: Make nullable to allow draft rentals without dates
            // This fixes the bug where creating a rental without dates throws an error
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE rentals ALTER COLUMN start_date DROP NOT NULL");
                DB::statement("ALTER TABLE rentals ALTER COLUMN end_date DROP NOT NULL");
            } else {
                DB::statement("ALTER TABLE rentals MODIFY COLUMN start_date DATE NULL");
                DB::statement("ALTER TABLE rentals MODIFY COLUMN end_date DATE NULL");
            }
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
