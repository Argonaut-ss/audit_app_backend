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
        Schema::create('uji_mutasi_kas', function (Blueprint $table) {
            $table->id('UjiMutasiID');
            $table->unsignedBigInteger('KasID')->unique();
            $table->text('Penjelasan')->nullable();
            $table->timestamps();

            $table->foreign('KasID', 'uji_mutasi_kas_kas_id_fk')
                ->references('KasID')
                ->on('kas')
                ->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uji_mutasi_kas');
    }
};
