<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_aset_tetap', function (Blueprint $table) {
            $table->id('JurnalKoreksiAsetTetapID');
            $table->unsignedBigInteger('AsetTetapID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('AsetTetapID', 'jkat_aset_tetap_id_fk')
                ->references('AsetTetapID')
                ->on('aset_tetap')
                ->cascadeOnDelete();

            $table->index('AsetTetapID', 'jkat_aset_tetap_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_aset_tetap', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiAsetTetapID');
            $table->unsignedBigInteger('JurnalKoreksiAsetTetapID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiAsetTetapID', 'pjkat_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiAsetTetapID')
                ->on('jurnal_koreksi_aset_tetap')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkat_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiAsetTetapID', 'pjkat_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkat_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_aset_tetap');
        Schema::dropIfExists('jurnal_koreksi_aset_tetap');
    }
};
