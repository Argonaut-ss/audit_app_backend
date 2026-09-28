<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_pendapatan_usaha', function (Blueprint $table) {
            $table->id('JurnalKoreksiPendapatanUsahaID');
            $table->unsignedBigInteger('PendapatanUsahaID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('PendapatanUsahaID', 'jkpu_pendapatan_usaha_id_fk')
                ->references('PendapatanUsahaID')
                ->on('pendapatan_usaha')
                ->cascadeOnDelete();

            $table->index('PendapatanUsahaID', 'jkpu_pendapatan_usaha_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_pendapatan_usaha', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiPendapatanUsahaID');
            $table->unsignedBigInteger('JurnalKoreksiPendapatanUsahaID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiPendapatanUsahaID', 'pjkpu_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiPendapatanUsahaID')
                ->on('jurnal_koreksi_pendapatan_usaha')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkpu_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiPendapatanUsahaID', 'pjkpu_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkpu_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_pendapatan_usaha');
        Schema::dropIfExists('jurnal_koreksi_pendapatan_usaha');
    }
};
