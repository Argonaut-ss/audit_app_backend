<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_aset_tetap', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('aset_tetap_id');

            $table->foreign('aset_tetap_id')
                ->references('AsetTetapID')
                ->on('aset_tetap')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['aset_tetap_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedur_aset_tetap');
    }
};