<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_hasil_audits', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('JwbKasusID')->unique();

            $table->string('opini_audit');

            $table->timestamps();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_hasil_audits');
    }
};