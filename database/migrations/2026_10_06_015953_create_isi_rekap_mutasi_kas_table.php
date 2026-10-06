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
        Schema::create('isi_rekap_mutasi_kas', function (Blueprint $table) {
            $table->id('IsiRekapMutasiID');
            $table->unsignedBigInteger('RekapMutasiID');
            $table->date('Tanggal');
            $table->string('Keterangan')->nullable();
            $table->bigInteger('Debit')->default(0);
            $table->bigInteger('Kredit')->default(0);
            $table->bigInteger('Saldo')->default(0);
            $table->timestamps();

            $table->foreign('RekapMutasiID', 'isi_rekap_mutasi_kas_rekap_id_fk')
                ->references('RekapMutasiID')
                ->on('rekap_mutasi_kas')
                ->cascadeOnDelete();

            $table->index('RekapMutasiID', 'isi_rekap_mutasi_kas_rekap_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isi_rekap_mutasi_kas');
    }
};
