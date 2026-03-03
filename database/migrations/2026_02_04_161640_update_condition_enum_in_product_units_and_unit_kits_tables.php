<?php

use App\Helpers\DatabaseHelper;
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
            DatabaseHelper::modifyEnumColumn(
                'product_units',
                'condition',
                ['excellent', 'good', 'fair', 'poor', 'broken', 'lost'],
                'excellent',
                false
            );
    
            // Update unit_kits table
            DatabaseHelper::modifyEnumColumn(
                'unit_kits',
                'condition',
                ['excellent', 'good', 'fair', 'poor', 'broken', 'lost'],
                'excellent',
                false
            );
            
            // Update delivery_items table as well to be safe
            DatabaseHelper::modifyEnumColumn(
                'delivery_items',
                'condition',
                ['excellent', 'good', 'fair', 'poor', 'broken', 'lost'],
                null,
                true
            );
            
            // Update rental_item_kits table
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_out',
                ['excellent', 'good', 'fair', 'poor', 'broken', 'lost'],
                'excellent',
                false
            );
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_in',
                ['excellent', 'good', 'fair', 'poor', 'broken', 'lost'],
                null,
                true
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Revert product_units table
            DatabaseHelper::modifyEnumColumn(
                'product_units',
                'condition',
                ['excellent', 'good', 'fair', 'poor'],
                'excellent',
                false
            );

            // Revert unit_kits table
            DatabaseHelper::modifyEnumColumn(
                'unit_kits',
                'condition',
                ['excellent', 'good', 'fair', 'poor'],
                'excellent',
                false
            );
            
            // Revert delivery_items table
            DatabaseHelper::modifyEnumColumn(
                'delivery_items',
                'condition',
                ['excellent', 'good', 'fair', 'poor'],
                null,
                true
            );
            
            // Revert rental_item_kits table
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_out',
                ['excellent', 'good', 'fair', 'poor'],
                'excellent',
                false
            );
            DatabaseHelper::modifyEnumColumn(
                'rental_item_kits',
                'condition_in',
                ['excellent', 'good', 'fair', 'poor'],
                null,
                true
            );
        }
    }
};
