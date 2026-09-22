<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosedur_persediaan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('persediaan_id');

            $table->foreign('persediaan_id')
                ->references('PersediaanID')
                ->on('persediaan')
                ->onDelete('cascade');

            $table->string('nama_prosedur');
            $table->string('index')->nullable();
            $table->date('tanggal');
            $table->boolean('checkbox')->default(false);

            $table->timestamps();

            $table->index(['persediaan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosedur_persediaan');
    }
};