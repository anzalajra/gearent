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
        // PostgreSQL: Change column to string and add check constraint
        DB::statement("ALTER TABLE deliveries ALTER COLUMN status TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE deliveries DROP CONSTRAINT IF EXISTS deliveries_status_check");
        DB::statement("ALTER TABLE deliveries ADD CONSTRAINT deliveries_status_check CHECK (status IN ('draft', 'pending', 'completed', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE deliveries DROP CONSTRAINT IF EXISTS deliveries_status_check");
        DB::statement("ALTER TABLE deliveries ADD CONSTRAINT deliveries_status_check CHECK (status IN ('draft', 'completed'))");
    }
};
