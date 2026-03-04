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
            $table->enum('condition_out', ['excellent', 'good', 'fair', 'poor']); // Kondisi saat keluar
            $table->enum('condition_in', ['excellent', 'good', 'fair', 'poor'])->nullable(); // Kondisi saat kembali
            $table->boolean('is_returned')->default(false); // Apakah sudah dikembalikan
            $table->text('notes')->nullable(); // Catatan (misal: rusak, hilang)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_item_kits');
    }
};