<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('JurnalKoreksi', function (Blueprint $table) {
            $table->id('JurnalKoreksiID');
            $table->unsignedBigInteger('PiutangID');
            $table->text('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();

            $table->index('PiutangID');
        });

        Schema::create('PembayaranJurnalKoreksi', function (Blueprint $table) {
            $table->id('PembayaranJurnalKoreksiID');
            $table->unsignedBigInteger('JurnalKoreksiID');
            $table->unsignedBigInteger('COAID');
            $table->bigInteger('Debet')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->timestamps();

            $table->foreign('JurnalKoreksiID')
                ->references('JurnalKoreksiID')
                ->on('JurnalKoreksi')
                ->cascadeOnDelete();

            $table->foreign('COAID')
                ->references('COAID')
                ->on('coa')
                ->restrictOnDelete();

            $table->index('JurnalKoreksiID');
            $table->index('COAID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PembayaranJurnalKoreksi');
        Schema::dropIfExists('JurnalKoreksi');
    }
};
