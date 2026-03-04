<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->default('excellent');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_kits');
    }
};