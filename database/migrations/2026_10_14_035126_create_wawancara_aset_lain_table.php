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
        Schema::create('wawancara_aset_lain', function (Blueprint $table) {
            $table->id('WawancaraID');
            $table->unsignedBigInteger('AsetLainID');
            $table->text('Pertanyaan');
            $table->text('Jawaban')->nullable();
            $table->timestamps();

            $table->foreign('AsetLainID', 'wawancara_aset_lain_aset_lain_id_fk')
                ->references('AsetLainID')
                ->on('aset_lain')
                ->cascadeOnDelete();

            $table->index('AsetLainID', 'wawancara_aset_lain_aset_lain_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wawancara_aset_lain');
    }
};
