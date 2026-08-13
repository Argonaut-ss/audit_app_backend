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
         * TABEL KASUS
         * =====================================================
         */

        Schema::create('kasus', function (Blueprint $table) {
            $table->id('KasusID'); // PK
            $table->string('KelasID');
            $table->enum('TipeKelas', [
                'UTS',
                'UAS',
                'Tugas',
                'Sandbox',
            ]);

            $table->unsignedBigInteger('ClientID');
            $table->string('NamaTugas');
            $table->string('NamaFile');

            $table->unique(
                ['KelasID', 'TipeKelas'],
                'kasus_kelas_tipe_unique'
            );
        });


        /*
         * =====================================================
         * INDEX KODE KELAS
         * =====================================================
         */

        Schema::table('kelas', function (Blueprint $table) {

            $table->index(
                'kode_kelas',
                'kelas_kode_kelas_index'
            );
        });


        /* Kasus.KelasID
         *       ↓
         * Kelas.kode_kelas
         */

        Schema::table('kasus', function (Blueprint $table) {

            $table->foreign('KelasID')
                ->references('kode_kelas')
                ->on('kelas')
                ->cascadeOnDelete();
        });

        Schema::table('kasus', function (Blueprint $table) {

            $table->foreign('ClientID')
                ->references('ClientID')
                ->on('data_client')
                ->restrictOnDelete();
        });

        DB::statement(
            'ALTER TABLE kasus ADD File MEDIUMBLOB NOT NULL'
        );
    }


    public function down(): void
    {
        Schema::table('kasus', function (Blueprint $table) {

            $table->dropForeign([
                'ClientID',
            ]);
        });

        Schema::table('kasus', function (Blueprint $table) {

            $table->dropForeign([
                'KelasID',
            ]);
        });

        Schema::table('kelas', function (Blueprint $table) {

            $table->dropIndex(
                'kelas_kode_kelas_index'
            );
        });

        Schema::dropIfExists('kasus');
    }
};