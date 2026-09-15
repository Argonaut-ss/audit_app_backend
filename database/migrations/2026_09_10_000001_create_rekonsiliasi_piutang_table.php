<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_piutang', function (Blueprint $table) {
            $table->id('RekonsiliasiPiutangID');
            $table->unsignedBigInteger('PiutangID');
            $table->unsignedBigInteger('KonfirmasiPiutangID')
                ->nullable();
            $table->string('NomorFaktur');
            $table->date('TanggalFaktur');
            $table->bigInteger('SaldoBuku')->default(0);
            $table->bigInteger('SaldoCustomer')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->string('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();

            $table->index('PiutangID');
            $table->index(
                'KonfirmasiPiutangID',
                'rekonsiliasi_piutang_konfirmasi_piutang_id_idx'
            );

            $table->foreign(
                'KonfirmasiPiutangID',
                'rekonsiliasi_piutang_konfirmasi_piutang_id_fk'
            )
                ->references('KonfirmasiPiutangID')
                ->on('KonfirmasiPiutang')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_piutang');
    }
};
