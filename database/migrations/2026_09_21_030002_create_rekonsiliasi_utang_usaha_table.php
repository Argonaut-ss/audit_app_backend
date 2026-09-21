<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_utang_usaha', function (Blueprint $table) {
            $table->id('RekonsiliasiUtangUsahaID');
            $table->unsignedBigInteger('UtangUsahaID');
            $table->unsignedBigInteger('KonfirmasiUtangUsahaID')
                ->nullable();
            $table->string('NomorFaktur');
            $table->date('TanggalFaktur');
            $table->bigInteger('SaldoBuku');
            $table->bigInteger('SaldoCustomer');
            $table->bigInteger('Selisih');
            $table->string('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('UtangUsahaID')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->cascadeOnDelete();

            $table->index('UtangUsahaID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_utang_usaha');
    }
};
