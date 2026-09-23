<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opname_persediaan', function (Blueprint $table) {
            $table->id('StokOpnameID');
            $table->unsignedBigInteger('PersediaanID');

            $table->string('NamaPersediaan')->nullable();
            $table->string('Satuan')->nullable();
            $table->bigInteger('SaldoNeraca')->default(0);
            $table->bigInteger('JumlahSistem')->default(0);
            $table->bigInteger('JumlahFisik')->default(0);
            $table->bigInteger('SelisihFisik')->default(0);
            $table->bigInteger('SelisihSistem')->default(0);
            $table->string('Keterangan')->nullable();

            $table->timestamps();

            $table->foreign('PersediaanID', 'sopers_persediaan_id_fk')
                ->references('PersediaanID')
                ->on('persediaan')
                ->cascadeOnDelete();

            $table->index('PersediaanID', 'sopers_persediaan_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_persediaan');
    }
};
