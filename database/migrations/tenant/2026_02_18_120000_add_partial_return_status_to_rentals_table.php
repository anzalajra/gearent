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
                ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return', 'partial_return'],
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
        // Reverting this is tricky if there are records with 'partial_return' status.
        // We will just keep it or revert to previous known state if possible, but for now let's just allow rolling back to previous list.
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
};
