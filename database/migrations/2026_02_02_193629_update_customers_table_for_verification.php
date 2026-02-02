<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop old columns if exist
            if (Schema::hasColumn('customers', 'id_type')) {
                $table->dropColumn('id_type');
            }
            if (Schema::hasColumn('customers', 'id_card_image')) {
                $table->dropColumn('id_card_image');
            }

            // Add new columns
            if (!Schema::hasColumn('customers', 'nik')) {
                $table->string('nik', 16)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('email_verified_at');
            }
            if (!Schema::hasColumn('customers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_verified');
            }
            if (!Schema::hasColumn('customers', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['nik', 'is_verified', 'verified_at', 'verified_by']);
        });
    }
};