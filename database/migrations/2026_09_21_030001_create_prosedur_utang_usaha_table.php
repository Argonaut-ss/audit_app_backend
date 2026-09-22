<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_utang_usaha', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('utang_usaha_id');

            $table->foreign('utang_usaha_id')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['utang_usaha_id']);
        });
    }
};