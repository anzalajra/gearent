<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Rental;
use App\Models\Quotation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all rentals with status 'pending' to 'quotation'
        $rentals = Rental::where('status', 'pending')->get();
        
        foreach ($rentals as $rental) {
            $rental->update(['status' => Rental::STATUS_QUOTATION]);
            
            // Create Quotation if not exists
            if (!$rental->quotation_id) {
                $quotation = Quotation::create([
                    'user_id' => $rental->user_id,
                    'date' => $rental->created_at,
                    'valid_until' => $rental->created_at->addDays(7),
                    'status' => Quotation::STATUS_ON_QUOTE,
                    'subtotal' => $rental->subtotal,
                    'tax' => 0,
                    'total' => $rental->total,
                    'notes' => $rental->notes,
                ]);
                
                $rental->update(['quotation_id' => $quotation->id]);
            }
        }
        
        // Also ensure any rental with status 'quotation' but NO quotation_id gets one
        $rentalsWithoutQuotation = Rental::where('status', Rental::STATUS_QUOTATION)
            ->whereNull('quotation_id')
            ->get();
            
        foreach ($rentalsWithoutQuotation as $rental) {
             $quotation = Quotation::create([
                'user_id' => $rental->user_id,
                'date' => $rental->created_at,
                'valid_until' => $rental->created_at->addDays(7),
                'status' => Quotation::STATUS_ON_QUOTE,
                'subtotal' => $rental->subtotal,
                'tax' => 0,
                'total' => $rental->total,
                'notes' => $rental->notes,
            ]);
            
            $rental->update(['quotation_id' => $quotation->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse logic needed as this is a data migration
    }
};
