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
        if (DB::getDriverName() !== 'sqlite') {
            // Update product_units table
            DB::statement("ALTER TABLE product_units MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor', 'broken', 'lost') NOT NULL DEFAULT 'excellent'");
    
            // Update unit_kits table
            DB::statement("ALTER TABLE unit_kits MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor', 'broken', 'lost') NOT NULL DEFAULT 'excellent'");
            
            // Update delivery_items table as well to be safe
            DB::statement("ALTER TABLE delivery_items MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor', 'broken', 'lost') NULL DEFAULT NULL");
            
            // Update rental_item_kits table
            DB::statement("ALTER TABLE rental_item_kits MODIFY COLUMN `condition_out` ENUM('excellent', 'good', 'fair', 'poor', 'broken', 'lost') NOT NULL DEFAULT 'excellent'");
            DB::statement("ALTER TABLE rental_item_kits MODIFY COLUMN `condition_in` ENUM('excellent', 'good', 'fair', 'poor', 'broken', 'lost') NULL DEFAULT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert product_units table
        DB::statement("ALTER TABLE product_units MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor') NOT NULL DEFAULT 'excellent'");

        // Revert unit_kits table
        DB::statement("ALTER TABLE unit_kits MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor') NOT NULL DEFAULT 'excellent'");
        
        // Revert delivery_items table
        DB::statement("ALTER TABLE delivery_items MODIFY COLUMN `condition` ENUM('excellent', 'good', 'fair', 'poor') NULL DEFAULT NULL");
        
        // Revert rental_item_kits table
        DB::statement("ALTER TABLE rental_item_kits MODIFY COLUMN `condition_out` ENUM('excellent', 'good', 'fair', 'poor') NOT NULL DEFAULT 'excellent'");
        DB::statement("ALTER TABLE rental_item_kits MODIFY COLUMN `condition_in` ENUM('excellent', 'good', 'fair', 'poor') NULL DEFAULT NULL");
    }
};
