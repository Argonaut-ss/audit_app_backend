<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_persediaan', function (Blueprint $table) {
            $table->id('JurnalKoreksiPersediaanID');
            $table->unsignedBigInteger('PersediaanID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('PersediaanID', 'jkpers_persediaan_id_fk')
                ->references('PersediaanID')
                ->on('persediaan')
                ->cascadeOnDelete();

            $table->index('PersediaanID', 'jkpers_persediaan_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_persediaan', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiPersediaanID');
            $table->unsignedBigInteger('JurnalKoreksiPersediaanID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiPersediaanID', 'pjkpers_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiPersediaanID')
                ->on('jurnal_koreksi_persediaan')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkpers_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiPersediaanID', 'pjkpers_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkpers_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_persediaan');
        Schema::dropIfExists('jurnal_koreksi_persediaan');
    }
};