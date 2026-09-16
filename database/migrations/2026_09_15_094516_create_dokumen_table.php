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
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id('DokumenID');

            $table->foreignId('PiutangID')
                ->constrained('piutang', 'PiutangID')
                ->cascadeOnDelete();

            $table->enum('TipeFile', [
                'Rincian',
                'Buku Besar',
                'Lain-lain',
            ]);

            $table->string('NamaFile');

            $table->enum('TersediaDokumen', [
                'Ya',
                'Tidak',
            ]);

            $table->text('Alasan')->nullable();

            $table->mediumBlob('File')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};