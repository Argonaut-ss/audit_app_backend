<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasus', function (Blueprint $table) {

            $table->id('KasusID');

            // KelasID menyimpan kode_kelas
            $table->string('KelasID');

            $table->string('NamaTugas');

            $table->string('NamaFile');

            // 1 kelas hanya boleh memiliki 1 tugas
            $table->unique('KelasID');

            // Relasi ke kelas.kode_kelas
            $table->foreign('KelasID')
                ->references('kode_kelas')
                ->on('kelas')
                ->cascadeOnDelete();
        });

        /*
         * MEDIUMBLOB digunakan karena file PDF
         * dapat berukuran sampai 10 MB.
         */
        DB::statement(
            'ALTER TABLE kasus ADD File MEDIUMBLOB NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('kasus');
    }
};