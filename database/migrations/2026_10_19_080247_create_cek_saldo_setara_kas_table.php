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
        Schema::create('cek_saldo_setara_kas', function (Blueprint $table) {
            $table->id('CekSaldoID');
            $table->unsignedBigInteger('SetaraKasID');
            $table->string('NamaBank');
            $table->bigInteger('SaldoBukuBesar');
            $table->bigInteger('SaldoRekeningKoran');
            $table->bigInteger('Selisih');
            $table->timestamps();

            $table->foreign('SetaraKasID')
                ->references('SetaraKasID')
                ->on('setara_kas')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cek_saldo_setara_kas');
    }
};
