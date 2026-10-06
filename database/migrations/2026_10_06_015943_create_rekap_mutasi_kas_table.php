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
        Schema::create('rekap_mutasi_kas', function (Blueprint $table) {
            $table->id('RekapMutasiID');
            $table->unsignedBigInteger('KasID');
            $table->bigInteger('SaldoAwal')->default(0);
            $table->bigInteger('DebitTotal')->default(0);
            $table->bigInteger('KreditTotal')->default(0);
            $table->timestamps();

            $table->foreign('KasID', 'rekap_mutasi_kas_kas_id_fk')
                ->references('KasID')
                ->on('kas')
                ->cascadeOnDelete();

            $table->index('KasID', 'rekap_mutasi_kas_kas_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_mutasi_kas');
    }
};
