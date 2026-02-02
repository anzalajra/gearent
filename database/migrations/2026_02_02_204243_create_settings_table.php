<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('label');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default settings
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Gearent', 'type' => 'text', 'label' => 'Site Name', 'sort_order' => 1],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Professional Equipment Rental', 'type' => 'text', 'label' => 'Tagline', 'sort_order' => 2],
            ['group' => 'general', 'key' => 'site_email', 'value' => 'info@gearent.com', 'type' => 'email', 'label' => 'Email', 'sort_order' => 3],
            ['group' => 'general', 'key' => 'site_phone', 'value' => '021-1234567', 'type' => 'text', 'label' => 'Phone', 'sort_order' => 4],
            ['group' => 'general', 'key' => 'site_address', 'value' => 'Jakarta, Indonesia', 'type' => 'textarea', 'label' => 'Address', 'sort_order' => 5],
            
            // Rental
            ['group' => 'rental', 'key' => 'deposit_percentage', 'value' => '30', 'type' => 'number', 'label' => 'Deposit Percentage (%)', 'sort_order' => 1],
            ['group' => 'rental', 'key' => 'late_fee_percentage', 'value' => '10', 'type' => 'number', 'label' => 'Late Fee per Day (%)', 'sort_order' => 2],
            ['group' => 'rental', 'key' => 'min_rental_days', 'value' => '1', 'type' => 'number', 'label' => 'Minimum Rental Days', 'sort_order' => 3],
            ['group' => 'rental', 'key' => 'max_rental_days', 'value' => '30', 'type' => 'number', 'label' => 'Maximum Rental Days', 'sort_order' => 4],
            
            // WhatsApp
            ['group' => 'whatsapp', 'key' => 'whatsapp_number', 'value' => '6281234567890', 'type' => 'text', 'label' => 'WhatsApp Number', 'sort_order' => 1],
            ['group' => 'whatsapp', 'key' => 'whatsapp_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable WhatsApp Notification', 'sort_order' => 2],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};