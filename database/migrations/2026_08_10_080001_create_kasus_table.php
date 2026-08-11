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
         * =====================================================
         * TABEL KASUS / TUGAS
         * =====================================================
         */

        Schema::create('kasus', function (Blueprint $table) {

            /*
             * Primary key.
             */
            $table->id('KasusID');

            /*
             * Kode kelas.
             *
             * Contoh:
             * LA01
             * LA02
             */
            $table->string('KelasID');

            /*
             * Tipe kelas.
             *
             * Contoh:
             * UTS
             * UAS
             * Tugas
             * Sandbox
             */
            $table->enum('TipeKelas', [
                'UTS',
                'UAS',
                'Tugas',
                'Sandbox',
            ]);

            /*
             * Nama tugas.
             */
            $table->string('NamaTugas');

            /*
             * Nama file PDF.
             */
            $table->string('NamaFile');

            /*
             * =================================================
             * SATU TUGAS UNTUK SATU KELAS + TIPE
             * =================================================
             *
             * Contoh:
             *
             * LA01 + UTS  -> hanya boleh 1
             * LA01 + UAS  -> hanya boleh 1
             *
             * Tetapi:
             *
             * LA01 + UTS
             * LA01 + UAS
             *
             * boleh ada bersamaan.
             */
            $table->unique(
                ['KelasID', 'TipeKelas'],
                'kasus_kelas_tipe_unique'
            );
        });

        /*
         * =====================================================
         * INDEX KODE KELAS
         * =====================================================
         *
         * Karena Kasus.KelasID mengarah ke
         * Kelas.kode_kelas.
         *
         * KITA TIDAK mengubah migration kelas.
         */
        Schema::table('kelas', function (Blueprint $table) {
            $table->index(
                'kode_kelas',
                'kelas_kode_kelas_index'
            );
        });

        /*
         * =====================================================
         * FOREIGN KEY
         * =====================================================
         */

        Schema::table('kasus', function (Blueprint $table) {

            $table->foreign('KelasID')
                ->references('kode_kelas')
                ->on('kelas')
                ->cascadeOnDelete();
        });

        /*
         * =====================================================
         * FILE PDF
         * =====================================================
         *
         * MEDIUMBLOB dapat menyimpan file sampai sekitar 16 MB.
         * Batas upload aplikasi kamu tetap dapat dibuat 10 MB.
         */

        DB::statement(
            'ALTER TABLE kasus ADD File MEDIUMBLOB NOT NULL'
        );
    }

    public function down(): void
    {
        /*
         * Hapus foreign key terlebih dahulu.
         */
        Schema::table('kasus', function (Blueprint $table) {

            $table->dropForeign([
                'KelasID',
            ]);
        });

        /*
         * Hapus index kode_kelas yang kita tambahkan
         * ke tabel kelas.
         */
        Schema::table('kelas', function (Blueprint $table) {

            $table->dropIndex(
                'kelas_kode_kelas_index'
            );
        });

        /*
         * Hapus tabel kasus.
         */
        Schema::dropIfExists('kasus');
    }
};