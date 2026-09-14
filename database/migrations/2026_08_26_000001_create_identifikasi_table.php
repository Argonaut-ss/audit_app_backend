<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifikasi', function (Blueprint $table) {
            $table->id('IdentifikasiID');
            $table->unsignedBigInteger('JwbKasusID')->unique();
            $table->year('Tahun')->nullable();
            $table->string('OpiniAudit')->nullable();
            $table->string('NoSuratPengesahan')->nullable();
            $table->string('LaporanSPT')->nullable();
            $table->string('NoSuratKeputusan')->nullable();
            $table->string('LaporanKeuangan')->nullable();
            $table->string('TipePerikatan')->nullable();
            $table->string('SumberDana')->nullable();
            $table->string('JenisPerikatan')->nullable();
            $table->string('TujuanTransaksi')->nullable();
            $table->string('StandardAkutansi')->nullable();
            $table->unsignedBigInteger('TotalAset')->nullable();
            $table->string('NamaKAP')->nullable();
            $table->unsignedBigInteger('Pendapatan')->nullable();
            $table->bigInteger('LabaRugi')->nullable();
            $table->string('KontakNama')->nullable();
            $table->string('KontakJabatan')->nullable();
            $table->string('KontakNomor')->nullable();
            $table->string('KontakEmail')->nullable();
            
            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        DB::statement('
            ALTER TABLE identifikasi
            ADD FileAkte MEDIUMBLOB NULL,
            ADD FileNPWP MEDIUMBLOB NULL,
            ADD FileStrukturOrg MEDIUMBLOB NULL
        ');
    }

    public function down(): void
    {
        Schema::table('identifikasi', function (Blueprint $table) {
            $table->dropForeign([
                'JwbKasusID',
            ]);
        });

        Schema::dropIfExists('identifikasi');
    }
};