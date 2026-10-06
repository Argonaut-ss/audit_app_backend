<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_kas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('kas_id');

            $table->foreign('kas_id')
                ->references('KasID')
                ->on('kas')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['kas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedur_kas');
    }
};