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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            
            // Limits
            $table->integer('max_users')->default(1);
            $table->integer('max_products')->default(100);
            $table->integer('max_storage_mb')->default(1024); // 1GB default
            $table->integer('max_domains')->default(1);
            $table->integer('max_rental_transactions_per_month')->nullable();
            
            // Features (JSON for flexibility)
            $table->json('features')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add subscription_plan_id to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('email')->nullable()->after('name');
            $table->foreignId('subscription_plan_id')->nullable()->after('email')->constrained('subscription_plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('trial')->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['name', 'email', 'subscription_plan_id', 'trial_ends_at', 'subscription_ends_at', 'status']);
        });

        Schema::dropIfExists('subscription_plans');
    }
};
