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
        // Create product_components table
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('child_product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['parent_product_id', 'child_product_id']);
        });

        // Update rental_items table
        Schema::table('rental_items', function (Blueprint $table) {
            $table->foreignId('parent_item_id')->nullable()->after('rental_id')->constrained('rental_items')->cascadeOnDelete();
            
            // Make product_unit_id nullable
            // We need to check if we can modify the column type directly
            $table->foreignId('product_unit_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropForeign(['parent_item_id']);
            $table->dropColumn('parent_item_id');
            
            // Revert product_unit_id to not nullable
            // Note: This might fail if there are null values
            // $table->foreignId('product_unit_id')->nullable(false)->change(); 
        });

        Schema::dropIfExists('product_components');
    }
};
