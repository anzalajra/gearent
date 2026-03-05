<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns that were previously on the deprecated customers table
     * but are now needed on the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'customer_category_id')) {
                $table->foreignId('customer_category_id')->nullable()->after('address')
                    ->constrained('customer_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('customer_category_id');
            }
            if (! Schema::hasColumn('users', 'tax_country')) {
                $table->string('tax_country')->nullable()->default('ID')->after('tax_address');
            }
            if (! Schema::hasColumn('users', 'tax_registration_number')) {
                $table->string('tax_registration_number')->nullable()->after('tax_country');
            }
            if (! Schema::hasColumn('users', 'is_tax_exempt')) {
                $table->boolean('is_tax_exempt')->default(false)->after('tax_registration_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['phone', 'address', 'customer_category_id', 'custom_fields', 'tax_country', 'tax_registration_number', 'is_tax_exempt'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'customer_category_id') {
                        $table->dropConstrainedForeignId('customer_category_id');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
