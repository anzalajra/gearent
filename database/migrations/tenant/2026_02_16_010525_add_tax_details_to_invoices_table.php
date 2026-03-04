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
        Schema::table('invoices', function (Blueprint $table) {
            // Rename customer_id to user_id if it exists
            if (Schema::hasColumn('invoices', 'customer_id') && !Schema::hasColumn('invoices', 'user_id')) {
                $table->renameColumn('customer_id', 'user_id');
            } elseif (!Schema::hasColumn('invoices', 'user_id')) {
                // If customer_id doesn't exist either, just add user_id
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('quotation_id');
            }

            // Tax Columns
            if (!Schema::hasColumn('invoices', 'tax_base')) {
                $table->decimal('tax_base', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('invoices', 'ppn_rate')) {
                $table->decimal('ppn_rate', 5, 2)->default(0)->after('tax_base');
            }
            if (!Schema::hasColumn('invoices', 'ppn_amount')) {
                $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
            }
            if (!Schema::hasColumn('invoices', 'pph_rate')) {
                $table->decimal('pph_rate', 5, 2)->default(0)->after('ppn_amount');
            }
            if (!Schema::hasColumn('invoices', 'pph_amount')) {
                $table->decimal('pph_amount', 15, 2)->default(0)->after('pph_rate');
            }
            if (!Schema::hasColumn('invoices', 'is_taxable')) {
                $table->boolean('is_taxable')->default(false)->after('pph_amount');
            }
            if (!Schema::hasColumn('invoices', 'price_includes_tax')) {
                $table->boolean('price_includes_tax')->default(false)->after('is_taxable');
            }
            if (!Schema::hasColumn('invoices', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('price_includes_tax');
            }
            if (!Schema::hasColumn('invoices', 'tax_invoice_number')) {
                $table->string('tax_invoice_number')->nullable()->after('tax_name');
            }
            if (!Schema::hasColumn('invoices', 'tax_invoice_date')) {
                $table->date('tax_invoice_date')->nullable()->after('tax_invoice_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'user_id')) {
                $table->renameColumn('user_id', 'customer_id');
            }

            $table->dropColumn([
                'tax_base',
                'ppn_rate',
                'ppn_amount',
                'pph_rate',
                'pph_amount',
                'is_taxable',
                'price_includes_tax',
                'tax_name',
                'tax_invoice_number',
                'tax_invoice_date',
            ]);
        });
    }
};
