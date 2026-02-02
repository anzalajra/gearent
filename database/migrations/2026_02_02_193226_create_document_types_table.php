<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default document types
        $types = [
            ['name' => 'KTP', 'code' => 'ktp', 'description' => 'Kartu Tanda Penduduk', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'SIM', 'code' => 'sim', 'description' => 'Surat Izin Mengemudi', 'is_required' => false, 'sort_order' => 2],
            ['name' => 'Kartu Keluarga', 'code' => 'kk', 'description' => 'Kartu Keluarga', 'is_required' => false, 'sort_order' => 3],
            ['name' => 'BPJS', 'code' => 'bpjs', 'description' => 'Kartu BPJS Kesehatan', 'is_required' => false, 'sort_order' => 4],
            ['name' => 'NPWP', 'code' => 'npwp', 'description' => 'Nomor Pokok Wajib Pajak', 'is_required' => false, 'sort_order' => 5],
            ['name' => 'Paspor', 'code' => 'passport', 'description' => 'Paspor', 'is_required' => false, 'sort_order' => 6],
            ['name' => 'KTM', 'code' => 'ktm', 'description' => 'Kartu Tanda Mahasiswa', 'is_required' => false, 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->insert(array_merge($type, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};