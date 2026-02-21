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
        // 1. Users Table (Tax Identity)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'npwp')) {
                $table->string('npwp')->nullable()->after('nik');
            }
            if (!Schema::hasColumn('users', 'tax_identity_name')) {
                $table->string('tax_identity_name')->nullable()->after('npwp')->comment('Name on Tax Document if different from profile name');
            }
            if (!Schema::hasColumn('users', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('tax_identity_name');
            }
            if (!Schema::hasColumn('users', 'is_pkp')) {
                $table->boolean('is_pkp')->default(false)->after('tax_address')->comment('Pengusaha Kena Pajak Status');
            }
            if (!Schema::hasColumn('users', 'tax_type')) {
                $table->string('tax_type')->default('individual')->after('is_pkp')->comment('individual, corporate, government');
            }
        });

        // 2. Products Table (Tax Configuration)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('description');
            }
            if (!Schema::hasColumn('products', 'price_includes_tax')) {
                $table->boolean('price_includes_tax')->default(false)->after('is_taxable');
            }
        });

        // 3. Invoices Table (Tax Calculation & Reporting)
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'tax_base')) {
                $table->decimal('tax_base', 15, 2)->default(0)->after('subtotal')->comment('Dasar Pengenaan Pajak (DPP)');
            }
            if (!Schema::hasColumn('invoices', 'ppn_rate')) {
                $table->decimal('ppn_rate', 5, 2)->default(11)->after('tax_base');
            }
            if (!Schema::hasColumn('invoices', 'ppn_amount')) {
                $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
            }
            if (!Schema::hasColumn('invoices', 'pph_rate')) {
                $table->decimal('pph_rate', 5, 2)->default(0)->after('ppn_amount');
            }
            if (!Schema::hasColumn('invoices', 'pph_amount')) {
                $table->decimal('pph_amount', 15, 2)->default(0)->after('pph_rate')->comment('Withholding Tax (PPh 23/Final)');
            }
            if (!Schema::hasColumn('invoices', 'tax_invoice_number')) {
                $table->string('tax_invoice_number')->nullable()->after('pph_amount')->comment('Nomor Faktur Pajak');
            }
            if (!Schema::hasColumn('invoices', 'tax_invoice_date')) {
                $table->date('tax_invoice_date')->nullable()->after('tax_invoice_number');
            }
        });

        // 4. Bills Table (Input VAT)
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('bills', 'tax_invoice_number')) {
                $table->string('tax_invoice_number')->nullable()->after('tax_amount');
            }
        });

        // 5. Finance Transactions Table (Expenses & Others)
        Schema::table('finance_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_transactions', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('finance_transactions', 'tax_invoice_number')) {
                $table->string('tax_invoice_number')->nullable()->after('tax_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'npwp', 'tax_identity_name', 'tax_address', 'is_pkp', 'tax_type']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'price_includes_tax']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tax_base', 'ppn_rate', 'ppn_amount', 'pph_rate', 'pph_amount', 'tax_invoice_number', 'tax_invoice_date']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_invoice_number']);
        });

        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_invoice_number']);
        });
    }
};
