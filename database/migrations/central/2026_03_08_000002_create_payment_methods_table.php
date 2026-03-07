<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('channel_code')->nullable();
            $table->string('display_name');
            $table->string('icon')->nullable();
            $table->decimal('admin_fee', 12, 2)->default(0);
            $table->enum('admin_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['payment_gateway_id', 'type', 'channel_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
