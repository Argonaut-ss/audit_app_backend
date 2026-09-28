<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_beban_usaha', function (Blueprint $table) {
            $table->id('JurnalKoreksiBebanUsahaID');
            $table->unsignedBigInteger('BebanUsahaID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('BebanUsahaID', 'jkbu_beban_usaha_id_fk')
                ->references('BebanUsahaID')
                ->on('beban_usaha')
                ->cascadeOnDelete();

            $table->index('BebanUsahaID', 'jkbu_beban_usaha_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_beban_usaha', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiBebanUsahaID');
            $table->unsignedBigInteger('JurnalKoreksiBebanUsahaID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiBebanUsahaID', 'pjkbu_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiBebanUsahaID')
                ->on('jurnal_koreksi_beban_usaha')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkbu_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiBebanUsahaID', 'pjkbu_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkbu_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_beban_usaha');
        Schema::dropIfExists('jurnal_koreksi_beban_usaha');
    }
};
