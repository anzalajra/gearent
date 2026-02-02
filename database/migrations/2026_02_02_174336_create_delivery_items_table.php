<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_item_kit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_checked')->default(false);
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'lost', 'broken'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};