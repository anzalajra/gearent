<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->foreignId('payment_gateway_id')->nullable()->after('payment_reference')
                ->constrained('payment_gateways')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->after('payment_gateway_id')
                ->constrained('payment_methods')->nullOnDelete();
            $table->json('payment_data')->nullable()->after('payment_method_id');
            $table->string('gateway_reference_id')->nullable()->after('payment_data');
            $table->index('gateway_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_gateway_id']);
            $table->dropForeign(['payment_method_id']);
            $table->dropIndex(['gateway_reference_id']);
            $table->dropColumn(['payment_gateway_id', 'payment_method_id', 'payment_data', 'gateway_reference_id']);
        });
    }
};
