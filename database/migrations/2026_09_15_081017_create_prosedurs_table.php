<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedurs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('piutang_id')
                ->constrained('piutangs')
                ->cascadeOnDelete();

            $table->unsignedInteger('no_urut');
            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['piutang_id', 'no_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedurs');
    }
};