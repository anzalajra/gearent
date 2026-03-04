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
            $table->foreignId('daily_discount_id')->nullable()->after('discount_id')->constrained('daily_discounts')->nullOnDelete();
            $table->decimal('daily_discount_amount', 15, 2)->default(0)->after('daily_discount_id');
            $table->foreignId('date_promotion_id')->nullable()->after('daily_discount_amount')->constrained('date_promotions')->nullOnDelete();
            $table->decimal('date_promotion_amount', 15, 2)->default(0)->after('date_promotion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['daily_discount_id']);
            $table->dropForeign(['date_promotion_id']);
            $table->dropColumn(['daily_discount_id', 'daily_discount_amount', 'date_promotion_id', 'date_promotion_amount']);
        });
    }
};
