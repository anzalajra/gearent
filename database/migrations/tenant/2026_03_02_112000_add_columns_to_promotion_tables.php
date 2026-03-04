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
        Schema::table('daily_discounts', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->text('description')->nullable()->after('name');
            $table->integer('min_days')->after('description')->comment('Minimum rental days to qualify');
            $table->integer('free_days')->after('min_days')->comment('Number of free days given');
            $table->decimal('max_discount_amount', 15, 2)->nullable()->after('free_days')->comment('Maximum discount in Rupiah');
            $table->boolean('is_active')->default(true)->after('max_discount_amount');
            $table->date('start_date')->nullable()->after('is_active');
            $table->date('end_date')->nullable()->after('start_date');
            $table->integer('priority')->default(0)->after('end_date')->comment('Higher priority applied first');
        });

        Schema::table('date_promotions', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->text('description')->nullable()->after('name');
            $table->enum('type', ['percentage', 'fixed'])->default('percentage')->after('description');
            $table->decimal('value', 15, 2)->after('type')->comment('Discount value (percentage or fixed amount)');
            $table->decimal('max_discount_amount', 15, 2)->nullable()->after('value');
            $table->date('promo_date')->after('max_discount_amount')->comment('Specific date for promotion');
            $table->boolean('recurring_yearly')->default(false)->after('promo_date')->comment('Apply same date every year');
            $table->boolean('is_active')->default(true)->after('recurring_yearly');
            $table->integer('priority')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_discounts', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'min_days', 'free_days', 'max_discount_amount', 'is_active', 'start_date', 'end_date', 'priority']);
        });

        Schema::table('date_promotions', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'type', 'value', 'max_discount_amount', 'promo_date', 'recurring_yearly', 'is_active', 'priority']);
        });
    }
};
