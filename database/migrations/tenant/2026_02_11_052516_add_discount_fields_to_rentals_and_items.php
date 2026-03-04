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
        Schema::table('rentals', function (Blueprint $table) {
            $table->string('discount_type')->default('fixed')->after('discount'); // fixed, percent
            $table->string('deposit_type')->default('fixed')->after('deposit'); // fixed, percent
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'deposit_type']);
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
