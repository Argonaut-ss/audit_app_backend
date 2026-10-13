<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_aset_lain', function (Blueprint $table) {
            $table->id('JurnalKoreksiAsetLainID');
            $table->unsignedBigInteger('AsetLainID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('AsetLainID', 'jkal_aset_lain_id_fk')
                ->references('AsetLainID')
                ->on('aset_lain')
                ->cascadeOnDelete();

            $table->index('AsetLainID', 'jkal_aset_lain_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_aset_lain', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiAsetLainID');
            $table->unsignedBigInteger('JurnalKoreksiAsetLainID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiAsetLainID', 'pjkal_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiAsetLainID')
                ->on('jurnal_koreksi_aset_lain')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkal_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiAsetLainID', 'pjkal_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkal_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_aset_lain');
        Schema::dropIfExists('jurnal_koreksi_aset_lain');
    }
};
