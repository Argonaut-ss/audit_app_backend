<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_pendapatan_usaha', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pendapatan_usaha_id');

            $table->foreign('pendapatan_usaha_id')
                ->references('PendapatanUsahaID')
                ->on('pendapatan_usaha')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['pendapatan_usaha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedur_pendapatan_usaha');
    }
};