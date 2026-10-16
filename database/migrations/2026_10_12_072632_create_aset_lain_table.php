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
        Schema::create('aset_lain', function (Blueprint $table) {
            $table->id('AsetLainID');
            $table->unsignedBigInteger('JwbKasusID')->unique();
            $table->boolean('ProsedurCheck')->default(false);
            $table->boolean('DokumenCheck')->default(false);
            $table->boolean('KonfirmasiCheck')->default(false);
            $table->boolean('RekapCheck')->default(false);
            $table->boolean('DokumentasiCheck')->default(false);
            $table->boolean('BuktiPendukungCheck')->default(false);
            $table->boolean('WawancaraCheck')->default(false);
            $table->boolean('JurnalCheck')->default(false);
            $table->text('Kesimpulan')->nullable();
            $table->timestamps();

            // FK ke JwbKasus
            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_lain');
    }
};
