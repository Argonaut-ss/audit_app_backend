<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_balasan_setara_kas', function (Blueprint $table) {
            $table->id('RekapBalasanSetaraKasID');
            $table->unsignedBigInteger('SetaraKasID');
            $table->unsignedBigInteger('CekSaldoSetaraKasID')->nullable();
            $table->bigInteger('SaldoBB')->default(0);
            $table->date('TanggalKirim')->nullable();
            $table->string('MetodeKirim')->nullable();
            $table->enum('StatusKonfirmasi', ['diterima', 'belum diterima'])->nullable();
            $table->date('TanggalJawab')->nullable();
            $table->bigInteger('SaldoJawab')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->bigInteger('KonfirmasiTakTerbalas')->default(0);
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->timestamps();

            $table->foreign('SetaraKasID')
                ->references('SetaraKasID')
                ->on('setara_kas')
                ->cascadeOnDelete();

            $table->foreign('CekSaldoSetaraKasID')
                ->references('CekSaldoID')
                ->on('cek_saldo_setara_kas')
                ->nullOnDelete();

            $table->index('SetaraKasID');
            $table->index('CekSaldoSetaraKasID');
        });

        DB::statement('ALTER TABLE rekap_balasan_setara_kas ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('rekap_balasan_setara_kas', function (Blueprint $table) {
            $table->dropForeign(['SetaraKasID']);
            $table->dropForeign(['CekSaldoSetaraKasID']);
        });
        Schema::dropIfExists('rekap_balasan_setara_kas');
    }
};
