<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update ProductUnit status enum
        DB::statement("ALTER TABLE product_units MODIFY COLUMN status ENUM('available', 'scheduled', 'rented', 'maintenance', 'retired') NOT NULL DEFAULT 'available'");
        
        // Update Rental status enum
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return') NOT NULL DEFAULT 'pending'");

        // Update Delivery status enum
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('draft', 'pending', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE product_units MODIFY COLUMN status ENUM('available', 'rented', 'maintenance', 'retired') NOT NULL DEFAULT 'available'");
        DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('draft', 'completed') NOT NULL DEFAULT 'draft'");
    }
};
