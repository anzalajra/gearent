<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the notifications.data column to be JSONB instead of TEXT.
     * This is required for PostgreSQL to support JSON operators like ->>
     * used by Filament's notification system.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Check if the column is not already jsonb
            $columnType = DB::selectOne("
                SELECT data_type 
                FROM information_schema.columns 
                WHERE table_name = 'notifications' AND column_name = 'data'
            ");
            
            if ($columnType && $columnType->data_type !== 'jsonb') {
                DB::statement("ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't revert - keeping jsonb is fine
    }
};
