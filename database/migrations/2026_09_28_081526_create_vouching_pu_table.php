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
        Schema::create('vouching_pu', function (Blueprint $table) {
            $table->id('VouchingID');
            $table->unsignedBigInteger('PendapatanUsahaID');
            $table->string('Keterangan')->nullable();
            $table->date('Tanggal');
            $table->string('NomorBukti');
            $table->bigInteger('NominalInternal')->default(0);
            $table->string('BuktiInternalTipeFile')->nullable();
            $table->bigInteger('NominalEksternal')->default(0);
            $table->string('BuktiEksternalTipeFile')->nullable();
            $table->bigInteger('Selisih')->default(0);
            $table->boolean('PihakRelasi')->default(false);
            $table->string('BuktiFileTipe')->nullable();
            $table->boolean('ApprovalPO')->default(false);
            $table->boolean('ApprovalNV')->default(false);
            $table->boolean('ApprovalDO')->default(false);
            $table->timestamps();

            $table->foreign('PendapatanUsahaID', 'vouching_pu_pendapatan_usaha_fk')
                ->references('PendapatanUsahaID')
                ->on('pendapatan_usaha')
                ->cascadeOnDelete();

            $table->index('PendapatanUsahaID', 'vouching_pu_pendapatan_usaha_idx');
        });

        DB::statement(
            'ALTER TABLE `vouching_pu` '
            . 'ADD `BuktiInternal` MEDIUMBLOB NULL, '
            . 'ADD `BuktiEksternal` MEDIUMBLOB NULL, '
            . 'ADD `Bukti` MEDIUMBLOB NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouching_pu');
    }
};
