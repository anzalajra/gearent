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
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'pending',
                false
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: changing back might cause data truncation if there are 'confirmed' records
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'pending',
                false
            );
        }
    }
};
