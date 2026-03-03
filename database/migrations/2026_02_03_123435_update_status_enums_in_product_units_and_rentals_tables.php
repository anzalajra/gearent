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
            // Update ProductUnit status enum
            DatabaseHelper::modifyEnumColumn(
                'product_units',
                'status',
                ['available', 'scheduled', 'rented', 'maintenance', 'retired'],
                'available',
                false
            );
            
            // Update Rental status enum
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'active', 'completed', 'cancelled', 'late_pickup', 'late_return'],
                'pending',
                false
            );
    
            // Update Delivery status enum
            DatabaseHelper::modifyEnumColumn(
                'deliveries',
                'status',
                ['draft', 'pending', 'completed', 'cancelled'],
                'draft',
                false
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DatabaseHelper::modifyEnumColumn(
                'product_units',
                'status',
                ['available', 'rented', 'maintenance', 'retired'],
                'available',
                false
            );
            DatabaseHelper::modifyEnumColumn(
                'rentals',
                'status',
                ['pending', 'active', 'completed', 'cancelled'],
                'pending',
                false
            );
            DatabaseHelper::modifyEnumColumn(
                'deliveries',
                'status',
                ['draft', 'completed'],
                'draft',
                false
            );
        }
    }
};
