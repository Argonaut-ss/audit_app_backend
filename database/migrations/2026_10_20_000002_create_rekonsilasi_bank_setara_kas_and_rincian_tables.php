<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsilasi_bank_setara_kas', function (Blueprint $table) {
            $table->id('RekonsilasiBankID');
            $table->unsignedBigInteger('SetaraKasID');
            $table->unsignedBigInteger('CekSaldoID')->nullable();
            $table->bigInteger('SaldoSetelahRekonBank')->default(0);
            $table->bigInteger('SaldoSetelahRekonBuku')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->boolean('Status')->default(false);
            $table->timestamps();

            $table->foreign('SetaraKasID', 'rbsk_setara_kas_id_fk')
                ->references('SetaraKasID')
                ->on('setara_kas')
                ->cascadeOnDelete();

            $table->foreign('CekSaldoID', 'rbsk_cek_saldo_id_fk')
                ->references('CekSaldoID')
                ->on('cek_saldo_setara_kas')
                ->nullOnDelete();

            $table->index('SetaraKasID', 'rbsk_setara_kas_id_idx');
            $table->index('CekSaldoID', 'rbsk_cek_saldo_id_idx');
        });

        Schema::create('rincian_rekonsiliasi_bank_setara_kas', function (Blueprint $table) {
            $table->id('RincianRekonsiliasiBankID');
            $table->unsignedBigInteger('RekonsilasiBankID');
            $table->enum('Kategori', [
                'BankBertambah',
                'BankBerkurang',
                'BukuBertambah',
                'BukuBerkurang',
            ]);
            $table->string('Keterangan');
            $table->bigInteger('Nominal')->default(0);
            $table->timestamps();

            $table->foreign('RekonsilasiBankID', 'rrbsk_rekonsilasi_bank_id_fk')
                ->references('RekonsilasiBankID')
                ->on('rekonsilasi_bank_setara_kas')
                ->cascadeOnDelete();

            // Composite Index untuk performa query instan & terisolasi per kategori
            $table->index(['RekonsilasiBankID', 'Kategori'], 'rrbsk_bank_kategori_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_rekonsiliasi_bank_setara_kas');
        Schema::dropIfExists('rekonsilasi_bank_setara_kas');
    }
};

