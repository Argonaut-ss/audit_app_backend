<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('DokumenKas', function (Blueprint $table) {
            $table->id('DokumenKasID');

            $table->foreignId('KasID')
                ->constrained('kas', 'KasID')
                ->cascadeOnDelete();

            $table->enum('TipeFile', [
                'Rincian',
                'Buku Besar',
                'Lain-lain',
            ]);

            $table->string('NamaFile');
            $table->string('NamaFileUpload')->nullable();
            $table->string('MimeType', 100)->nullable();
            $table->binary('File')->nullable();

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE `DokumenKas` MODIFY `File` MEDIUMBLOB NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DokumenKas');
    }
};