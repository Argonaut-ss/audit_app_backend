<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ProsedurAlternatif', function (Blueprint $table) {
            $table->id('ProsedurAlternatifID');
            $table->unsignedBigInteger('PiutangID');
            $table->unsignedBigInteger('KonfirmasiPiutangID')->nullable();
            $table->bigInteger('SaldoAkhir')->nullable();
            $table->boolean('KonfirmasiBayar')->nullable();
            $table->string('BuktiBayar')->nullable();
            $table->bigInteger('SaldoBata')->nullable();
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();

            $table->foreign('KonfirmasiPiutangID')
                ->references('KonfirmasiPiutangID')
                ->on('KonfirmasiPiutang')
                ->cascadeOnDelete();

            $table->unique(['PiutangID', 'KonfirmasiPiutangID']);
            $table->index('KonfirmasiPiutangID');
        });

        DB::statement('ALTER TABLE ProsedurAlternatif ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('ProsedurAlternatif', function (Blueprint $table) {
            $table->dropForeign(['PiutangID']);
            $table->dropForeign(['KonfirmasiPiutangID']);
        });

        Schema::dropIfExists('ProsedurAlternatif');
    }
};