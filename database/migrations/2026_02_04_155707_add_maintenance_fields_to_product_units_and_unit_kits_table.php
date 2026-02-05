<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable()->after('notes');
            $table->string('maintenance_status')->nullable()->after('status'); // In Repair, Waiting Parts, etc.
        });

        Schema::table('unit_kits', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable()->after('notes');
            $table->string('maintenance_status')->nullable()->after('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['last_checked_at', 'maintenance_status']);
        });

        Schema::table('unit_kits', function (Blueprint $table) {
            $table->dropColumn(['last_checked_at', 'maintenance_status']);
        });
    }
};
