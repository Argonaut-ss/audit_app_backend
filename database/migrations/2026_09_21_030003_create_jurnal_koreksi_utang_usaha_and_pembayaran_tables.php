<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_utang_usaha', function (Blueprint $table) {
            $table->id('JurnalKoreksiUtangUsahaID');
            $table->unsignedBigInteger('UtangUsahaID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('UtangUsahaID', 'jkuu_utang_usaha_id_fk')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->cascadeOnDelete();

            $table->index('UtangUsahaID', 'jkuu_utang_usaha_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_utang_usaha', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiUtangUsahaID');
            $table->unsignedBigInteger('JurnalKoreksiUtangUsahaID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiUtangUsahaID', 'pjkuu_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiUtangUsahaID')
                ->on('jurnal_koreksi_utang_usaha')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjkuu_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiUtangUsahaID', 'pjkuu_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjkuu_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_utang_usaha');
        Schema::dropIfExists('jurnal_koreksi_utang_usaha');
    }
};
