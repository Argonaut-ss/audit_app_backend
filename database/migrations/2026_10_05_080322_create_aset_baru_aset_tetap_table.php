<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aset_baru_aset_tetap', function (Blueprint $table) {
            $table->id('AsetBaruID');
            $table->unsignedBigInteger('AsetTetapID');
            $table->string('NamaAset');
            $table->string('KodeAset');
            $table->date('Tanggal');
            $table->bigInteger('HargaPerolehan')->default(0);
            $table->string('TipeFileAset')->nullable();
            $table->string('TipeFileBukti')->nullable();
            $table->timestamps();

            $table->foreign('AsetTetapID', 'aset_baru_aset_tetap_parent_fk')
                ->references('AsetTetapID')
                ->on('aset_tetap')
                ->cascadeOnDelete();

            $table->index('AsetTetapID', 'aset_baru_aset_tetap_parent_idx');
        });

        DB::statement(
            'ALTER TABLE `aset_baru_aset_tetap` '
            . 'ADD `FotoAset` MEDIUMBLOB NULL, '
            . 'ADD `FotoBukti` MEDIUMBLOB NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_baru_aset_tetap');
    }
};
