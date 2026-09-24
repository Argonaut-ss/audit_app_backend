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
        Schema::create('uji_mutasi_persediaan', function (Blueprint $table) {
            $table->id('UjiMutasiID');
            $table->unsignedBigInteger('PersediaanID');
            $table->unsignedBigInteger('StokOpnameID');
            $table->bigInteger('SaldoStokOpname')->default(0);
            $table->bigInteger('Keluar')->default(0);
            $table->bigInteger('Rusak')->default(0);
            $table->bigInteger('Masuk')->default(0);
            $table->bigInteger('SaldoAuditSblm')->default(0);
            $table->bigInteger('SaldoAkhirSblm')->default(0);
            $table->bigInteger('SaldoAuditSdh')->default(0);
            $table->bigInteger('SaldoAkhirSdh')->default(0);
            $table->timestamps();

            $table->foreign('PersediaanID', 'ujimutasi_persediaan_id_fk')
                ->references('PersediaanID')
                ->on('persediaan')
                ->cascadeOnDelete();

            $table->index('PersediaanID', 'ujimutasi_persediaan_id_idx');
            $table->index('StokOpnameID', 'ujimutasi_stok_opname_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uji_mutasi_persediaan');
    }
};
