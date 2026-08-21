<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perikatan', function (Blueprint $table) {
            $table->id('PerikatanID');
            $table->unsignedBigInteger('JwbKasusID')->unique();

            $table->binary('FileProposal')->nullable();
            $table->binary('FileSPK')->nullable();
            $table->binary('FileSuratTugas')->nullable();
            $table->binary('FilePenugasan')->nullable();
            $table->binary('FileIndependensi')->nullable();

            $table->string('Pembuat')->nullable();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('perikatan', function (Blueprint $table) {
            $table->dropForeign([
                'JwbKasusID',
            ]);
        });

        Schema::dropIfExists('perikatan');
    }
};