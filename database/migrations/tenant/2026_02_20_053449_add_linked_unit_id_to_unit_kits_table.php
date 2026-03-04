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
        Schema::table('unit_kits', function (Blueprint $table) {
            $table->foreignId('linked_unit_id')->nullable()->after('unit_id')->constrained('product_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kits', function (Blueprint $table) {
            $table->dropForeign(['linked_unit_id']);
            $table->dropColumn('linked_unit_id');
        });
    }
};
