<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->change();
            $table->decimal('discount', 12, 2)->default(0)->change();
            $table->decimal('total', 12, 2)->default(0)->change();
            $table->decimal('deposit', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        // 
    }
};