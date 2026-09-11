<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KonfirmasiPiutang', function (Blueprint $table) {
            $table->id('KonfirmasiPiutangID');
            $table->unsignedBigInteger('PiutangID');
            $table->string('NamaCustomer');
            $table->string('KotaCustomer');
            $table->integer('Jumlah');
            $table->binary('File')->nullable();
            $table->string('NamaFile')->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE KonfirmasiPiutang MODIFY File MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('KonfirmasiPiutang', function (Blueprint $table) {
            $table->dropForeign(['PiutangID']);
        });

        Schema::dropIfExists('KonfirmasiPiutang');
    }
};