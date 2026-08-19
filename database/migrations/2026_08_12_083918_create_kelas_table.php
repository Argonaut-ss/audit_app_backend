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
            $table->string('kode_kelas');
            $table->string('hari');
            $table->string('jam');
            $table->string('ruangan');
            $table->string('periode');
            $table->enum('tipe_kelas', ['UTS', 'UAS', 'Tugas', 'Sandbox']);
            
            // Relasi ke tabel dosens (FK: dosen_id)
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();

            // Relasi 1:1 ke tabel kasus
            $table->unsignedBigInteger('KasusID')->nullable()->unique();
            
            // Menambahkan unique constraint untuk kombinasi hari, jam, dan ruangan
            $table->unique(['hari', 'jam', 'ruangan']);
            
            $table->timestamps();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreign('KasusID')
                ->references('KasusID')
                ->on('kasus')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['KasusID']);
        });

        Schema::dropIfExists('kelas');
    }
};