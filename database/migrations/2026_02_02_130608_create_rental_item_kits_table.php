<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_item_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_kit_id')->constrained()->cascadeOnDelete();
            $table->enum('condition_out', ['excellent', 'good', 'fair', 'poor'])->default('excellent');
            $table->enum('condition_in', ['excellent', 'good', 'fair', 'poor', 'lost', 'broken'])->nullable();
            $table->boolean('is_returned')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_item_kits');
    }
};