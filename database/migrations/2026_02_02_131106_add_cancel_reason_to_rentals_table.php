<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('notes');
            
            // Update status enum to include new statuses
            // Note: For MySQL, you might need to modify the column
        });

        // Update status column to include new values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'late_pickup', 'active', 'late_return', 'completed', 'cancelled') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });

        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};