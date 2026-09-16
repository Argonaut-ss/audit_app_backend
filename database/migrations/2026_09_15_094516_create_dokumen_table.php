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
                ->constrained('Piutang', 'PiutangID')
                ->cascadeOnDelete();

            $table->enum('TipeFile', [
                'Rincian',
                'Buku Besar',
                'Lain-lain',
            ]);

            $table->string('NamaFile');
            $table->string('NamaFileUpload')->nullable();
            $table->binary('File')->nullable();

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE `dokumen` MODIFY `File` MEDIUMBLOB NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};