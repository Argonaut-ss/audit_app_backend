<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_setara_kas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('setara_kas_id');

            $table->foreign('setara_kas_id')
                ->references('SetaraKasID')
                ->on('setara_kas')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['setara_kas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedur_setara_kas');
    }
};