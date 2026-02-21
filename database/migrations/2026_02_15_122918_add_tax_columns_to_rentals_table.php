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
        Schema::table('rentals', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(false)->after('discount');
            $table->boolean('price_includes_tax')->default(false)->after('is_taxable');
            
            $table->decimal('tax_base', 15, 2)->default(0)->after('price_includes_tax')->comment('DPP');
            $table->decimal('ppn_rate', 5, 2)->default(0)->after('tax_base');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
            $table->decimal('pph_rate', 5, 2)->default(0)->after('ppn_amount');
            $table->decimal('pph_amount', 15, 2)->default(0)->after('pph_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'is_taxable',
                'price_includes_tax',
                'tax_base',
                'ppn_rate',
                'ppn_amount',
                'pph_rate',
                'pph_amount',
            ]);
        });
    }
};
