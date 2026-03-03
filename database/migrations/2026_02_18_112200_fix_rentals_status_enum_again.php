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
        // Fix the status enum to include 'pending' and remove 'quotation'
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
        // Revert to the incorrect state if needed (though we probably don't want to)
        // But for correctness of rollback, we might just leave it or revert to what we think it was
        // Let's just keep it safe and maybe not revert to the broken state.
        // Or revert to a safe previous state if known.
        // For now, I will just leave it as is or revert to a generic state.
        if (DB::getDriverName() !== 'sqlite') {
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
