<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_balasan_aset_lain', function (Blueprint $table) {
            $table->id('RekapBalasanAsetLainID');
            $table->unsignedBigInteger('AsetLainID');
            $table->unsignedBigInteger('KonfirmasiAsetLainID')->nullable();
            $table->bigInteger('SaldoBB')->default(0);
            $table->date('TanggalKirim')->nullable();
            $table->string('MetodeKirim')->nullable();
            $table->date('TanggalJawab')->nullable();
            $table->bigInteger('SaldoJawab')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->enum('Status', ['terbalas', 'tidak terbalas'])->nullable();
            $table->timestamps();

            $table->foreign('AsetLainID')
                ->references('AsetLainID')
                ->on('aset_lain')
                ->cascadeOnDelete();

            $table->foreign('KonfirmasiAsetLainID')
                ->references('KonfirmasiAsetLainID')
                ->on('konfirmasi_aset_lain')
                ->nullOnDelete();

            $table->index('AsetLainID');
            $table->index('KonfirmasiAsetLainID');
        });

        DB::statement('ALTER TABLE rekap_balasan_aset_lain ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('rekap_balasan_aset_lain', function (Blueprint $table) {
            $table->dropForeign(['AsetLainID']);
            $table->dropForeign(['KonfirmasiAsetLainID']);
        });

        Schema::dropIfExists('rekap_balasan_aset_lain');
    }
};
