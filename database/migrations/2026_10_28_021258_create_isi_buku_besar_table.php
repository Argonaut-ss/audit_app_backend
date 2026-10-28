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
        Schema::create('isi_buku_besar', function (Blueprint $table) {
            $table->id('IsiBukuBesarID');
            $table->unsignedBigInteger('BukuBesarID');
            $table->unsignedBigInteger('COAID');
            $table->string('SubMapping')->nullable();

            $table->foreign('BukuBesarID')
                ->references('BukuBesarID')
                ->on('buku_besar')
                ->cascadeOnDelete();

            $table->foreign('COAID')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isi_buku_besar');
    }
};
