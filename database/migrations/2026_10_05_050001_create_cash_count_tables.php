<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header Cash Count — 1:1 dengan Kas.
        // Denominasi hardcoded (tetap), disimpan sebagai kolom jumlah lembar per nominal.
        Schema::create('cash_count', function (Blueprint $table) {
            $table->id('CashCountID');
            $table->unsignedBigInteger('KasID')->unique();

            // NamaPerusahaan tidak disimpan — diturunkan dari DataClient.NamaClient saat GET.
            $table->enum('JenisKas', ['Kas Kecil', 'Kas Besar'])->default('Kas Kecil');
            $table->date('TanggalCashCount')->nullable();

            // Jumlah lembar uang kertas (nominal fixed). UK = Uang Kertas.
            $table->integer('UK100k')->default(0);
            $table->integer('UK75k')->default(0);
            $table->integer('UK50k')->default(0);
            $table->integer('UK20k')->default(0);
            $table->integer('UK10k')->default(0);
            $table->integer('UK5k')->default(0);
            $table->integer('UK2k')->default(0);
            $table->integer('UK1k')->default(0);

            // Jumlah keping uang logam (nominal fixed). UL = Uang Logam.
            $table->integer('UL1k')->default(0);
            $table->integer('UL500')->default(0);
            $table->integer('UL200')->default(0);
            $table->integer('UL100')->default(0);

            $table->bigInteger('SaldoBuku')->default(0);
            $table->text('Penjelasan')->nullable();

            // Total dihitung backend saat simpan (hasil akhir yang disimpan).
            $table->bigInteger('TotalKertas')->default(0);
            $table->bigInteger('TotalLogam')->default(0);
            $table->bigInteger('TotalDanaLain')->default(0);
            $table->bigInteger('TotalKeseluruhan')->default(0);
            $table->bigInteger('SelisihLebihKurang')->default(0);

            $table->timestamps();

            $table->foreign('KasID', 'cc_kas_id_fk')
                ->references('KasID')
                ->on('kas')
                ->cascadeOnDelete();
        });

        // Dana Lain-Lain — 0..* dari Cash Count.
        Schema::create('cash_count_dana_lain', function (Blueprint $table) {
            $table->id('DanaLainID');
            $table->unsignedBigInteger('CashCountID');

            $table->string('Keterangan')->nullable();
            $table->bigInteger('Jumlah')->default(0);

            $table->timestamps();

            $table->foreign('CashCountID', 'ccdl_cash_count_id_fk')
                ->references('CashCountID')
                ->on('cash_count')
                ->cascadeOnDelete();

            $table->index('CashCountID', 'ccdl_cash_count_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_count_dana_lain');
        Schema::dropIfExists('cash_count');
    }
};
