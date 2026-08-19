<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwb_kasus', function (Blueprint $table) {
            $table->id('JwbKasusID');
            $table->string('SubmisID')->unique();
            $table->unsignedBigInteger('KasusID');
            $table->string('nim');
            $table->dateTime('TanggalUpload');
            $table->integer('Nilai')->nullable();

            // Satu mahasiswa hanya boleh menjawab
            // satu kasus satu kali
            $table->unique(
                ['KasusID', 'nim'],
                'jwb_kasus_kasus_nim_unique'
            );
        });

        /*
         * File jawaban
         */
        DB::statement(
            'ALTER TABLE jwb_kasus ADD File MEDIUMBLOB NULL'
        );

        Schema::table('jwb_kasus', function (Blueprint $table) {

            $table->foreign('KasusID')
                ->references('KasusID')
                ->on('kasus')
                ->cascadeOnDelete();
        });

        Schema::table('jwb_kasus', function (Blueprint $table) {

            $table->foreign('nim')
                ->references('nim')
                ->on('mahasiswas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jwb_kasus', function (Blueprint $table) {

            $table->dropForeign([
                'KasusID',
            ]);

            $table->dropForeign([
                'nim',
            ]);
        });

        Schema::dropIfExists('jwb_kasus');
    }
};