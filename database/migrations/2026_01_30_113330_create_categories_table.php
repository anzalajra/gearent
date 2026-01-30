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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Nama kategori
            $table->string('slug')->unique();          // URL-friendly name
            $table->text('description')->nullable();   // Deskripsi (opsional)
            $table->string('image')->nullable();       // Gambar kategori (opsional)
            $table->boolean('is_active')->default(true); // Status aktif
            $table->timestamps();                      // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};