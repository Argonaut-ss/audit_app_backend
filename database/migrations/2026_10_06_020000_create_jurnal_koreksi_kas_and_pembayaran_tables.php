<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_kas', function (Blueprint $table) {
            $table->id('JurnalKoreksiKasID');
            $table->unsignedBigInteger('KasID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('KasID', 'jkkas_kas_id_fk')
                ->references('KasID')
                ->on('kas')
                ->cascadeOnDelete();

            $table->index('KasID', 'jkkas_kas_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_kas', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiKasID');
            $table->unsignedBigInteger('JurnalKoreksiKasID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiKasID', 'pjkkas_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiKasID')
                ->on('jurnal_koreksi_kas')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkkas_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiKasID', 'pjkkas_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkkas_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_kas');
        Schema::dropIfExists('jurnal_koreksi_kas');
    }
};
