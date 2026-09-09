<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Piutang', function (Blueprint $table) {
            $table->id('PiutangID');

            $table->unsignedBigInteger('JwbKasusID');

            $table->boolean('ProsedurCheck')->default(false);
            $table->boolean('DokumenCheck')->default(false);
            $table->boolean('KonfirmasiCheck')->default(false);
            $table->boolean('RekapCheck')->default(false);
            $table->boolean('JurnalCheck')->default(false);
            $table->boolean('RekonsiliasiCheck')->default(false);
            $table->boolean('UmurCheck')->default(false);
            $table->boolean('ProsedurAltCheck')->default(false);

            $table->timestamps();

            // FK ke JwbKasus
            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('JwbKasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Piutang');
    }
};