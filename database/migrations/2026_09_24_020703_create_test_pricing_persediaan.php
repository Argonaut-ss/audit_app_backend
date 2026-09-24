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
        Schema::create('test_pricing_persediaan', function (Blueprint $table) {
            $table->id('TestPricingID');
            $table->unsignedBigInteger('PersediaanID');
            $table->unsignedBigInteger('StokOpnameID');
            $table->bigInteger('HargaAudit')->default(0);
            $table->bigInteger('KuantitasAudit')->default(0);
            $table->bigInteger('JumlahAudit')->default(0);
            $table->bigInteger('HargaPerusahaan')->default(0);
            $table->bigInteger('KuantitasPerusahaan')->default(0);
            $table->bigInteger('JumlahPerusahaan')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->timestamps();

            $table->foreign('PersediaanID', 'testpricing_persediaan_id_fk')
                ->references('PersediaanID')
                ->on('persediaan')
                ->cascadeOnDelete();

            $table->foreign('StokOpnameID', 'testpricing_stok_opname_id_fk')
                ->references('StokOpnameID')
                ->on('stok_opname_persediaan')
                ->cascadeOnDelete();

            $table->index('PersediaanID', 'testpricing_persediaan_id_idx');
            $table->index('StokOpnameID', 'testpricing_stok_opname_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_pricing_persediaan');
    }
};
