<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_alt_utang', function (Blueprint $table) {
            $table->id('ProsedurAlternatifUtangUsahaID');
            $table->unsignedBigInteger('UtangUsahaID');
            $table->unsignedBigInteger('KonfirmasiUtangUsahaID')->nullable();
            $table->bigInteger('SaldoAkhir')->nullable();
            $table->boolean('KonfirmasiBayar')->nullable();
            $table->string('BuktiBayar')->nullable();
            $table->bigInteger('SaldoBata')->nullable();
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->timestamps();

            $table->foreign('UtangUsahaID')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->cascadeOnDelete();

            $table->foreign('KonfirmasiUtangUsahaID')
                ->references('KonfirmasiUtangUsahaID')
                ->on('KonfirmasiUtangUsaha')
                ->cascadeOnDelete();

            $table->unique(['UtangUsahaID', 'KonfirmasiUtangUsahaID'], 'prosedur_alt_utang_unique');
            $table->index('KonfirmasiUtangUsahaID');
        });

        DB::statement('ALTER TABLE prosedur_alt_utang ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('prosedur_alt_utang', function (Blueprint $table) {
            $table->dropForeign(['UtangUsahaID']);
            $table->dropForeign(['KonfirmasiUtangUsahaID']);
        });

        Schema::dropIfExists('prosedur_alt_utang');
    }
};