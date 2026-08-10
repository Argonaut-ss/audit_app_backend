<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kelas')->unique();
            $table->string('hari');
            $table->string('jam');
            $table->string('ruangan');
            $table->string('periode');
            $table->enum('tipe_kelas', ['UTS', 'UAS', 'Tugas', 'Sandbox']);
            
            // Relasi ke tabel dosens (FK: dosen_id)
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};