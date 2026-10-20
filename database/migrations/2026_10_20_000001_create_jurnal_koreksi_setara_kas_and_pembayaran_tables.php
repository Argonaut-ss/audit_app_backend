<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_koreksi_setara_kas', function (Blueprint $table) {
            $table->id('JurnalKoreksiSetaraKasID');
            $table->unsignedBigInteger('SetaraKasID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('SetaraKasID', 'jksk_setara_kas_id_fk')
                ->references('SetaraKasID')
                ->on('setara_kas')
                ->cascadeOnDelete();

            $table->index('SetaraKasID', 'jksk_setara_kas_id_idx');
        });

        Schema::create('pembayaran_jurnal_koreksi_setara_kas', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiSetaraKasID');
            $table->unsignedBigInteger('JurnalKoreksiSetaraKasID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiSetaraKasID', 'pjksk_jurnal_koreksi_id_fk')
                ->references('JurnalKoreksiSetaraKasID')
                ->on('jurnal_koreksi_setara_kas')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'pjksk_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index('JurnalKoreksiSetaraKasID', 'pjksk_jurnal_koreksi_id_idx');
            $table->index('COAID', 'pjksk_coa_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_jurnal_koreksi_setara_kas');
        Schema::dropIfExists('jurnal_koreksi_setara_kas');
    }
};

