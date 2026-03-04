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
            $table->decimal('late_fee', 12, 2)->default(0)->after('total');
            $table->decimal('security_deposit_amount', 12, 2)->default(0)->after('late_fee');
            $table->string('security_deposit_status')->default('pending')->after('security_deposit_amount'); // pending, paid, refunded, forfeited, partial_refunded
            $table->decimal('down_payment_amount', 12, 2)->default(0)->after('security_deposit_status');
            $table->string('down_payment_status')->default('pending')->after('down_payment_amount'); // pending, paid
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('residual_value', 12, 2)->default(0)->after('purchase_price');
            $table->integer('useful_life')->default(60)->after('residual_value'); // in months, default 5 years
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'late_fee', 
                'security_deposit_amount', 
                'security_deposit_status',
                'down_payment_amount',
                'down_payment_status'
            ]);
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['residual_value', 'useful_life']);
        });
    }
};
