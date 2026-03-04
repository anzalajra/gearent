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
        Schema::table('tenants', function (Blueprint $table) {
            $table->integer('current_rental_transactions_month')
                ->default(0)
                ->after('status');

            $table->string('current_rental_month', 7)
                ->nullable()
                ->after('current_rental_transactions_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'current_rental_transactions_month',
                'current_rental_month',
            ]);
        });
    }
};

