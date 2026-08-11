<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Buat tabel kasus terlebih dahulu.
         */
        Schema::create('kasus', function (Blueprint $table) {

            /*
             * Primary key.
             */
            $table->id('KasusID');

            /*
             * KelasID menyimpan kode_kelas.
             *
             * Tipe harus sama dengan:
             *
             * kelas.kode_kelas
             *
             * yaitu VARCHAR(255).
             */
            $table->string('KelasID');

            /*
             * Nama tugas.
             */
            $table->string('NamaTugas');

            /*
             * Nama file PDF.
             */
            $table->string('NamaFile');

            /*
             * 1 kelas hanya boleh memiliki
             * 1 tugas.
             */
            $table->unique('KelasID');
        });

        /*
         * Tambahkan index pada kelas.kode_kelas
         * karena kolom tersebut akan menjadi
         * target foreign key.
         *
         * Kita TIDAK mengubah migration kelas.
         */
        Schema::table('kelas', function (Blueprint $table) {
            $table->index('kode_kelas');
        });

        /*
         * Tambahkan foreign key setelah
         * index kode_kelas tersedia.
         */
        Schema::table('kasus', function (Blueprint $table) {

            $table->foreign('KelasID')
                ->references('kode_kelas')
                ->on('kelas')
                ->cascadeOnDelete();
        });

        DB::statement(
            'ALTER TABLE kasus ADD File MEDIUMBLOB NOT NULL'
        );
    }

    public function down(): void
    {
        /*
         * Hapus foreign key.
         */
        Schema::table('kasus', function (Blueprint $table) {
            $table->dropForeign([
                'KelasID',
            ]);
        });

        /*
         * Hapus index yang kita tambahkan
         * pada tabel kelas.
         */
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropIndex([
                'kelas_kode_kelas_index',
            ]);
        });

        /*
         * Hapus tabel kasus.
         */
        Schema::dropIfExists('kasus');
    }
};