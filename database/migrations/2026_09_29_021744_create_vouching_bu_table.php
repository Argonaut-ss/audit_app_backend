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
        Schema::create('vouching_bu', function (Blueprint $table) {
            $table->id('VouchingID');
            $table->unsignedBigInteger('BebanUsahaID');
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

            $table->foreign('BebanUsahaID', 'vouching_bu_beban_usaha_fk')
                ->references('BebanUsahaID')
                ->on('beban_usaha')
                ->cascadeOnDelete();

            $table->index('BebanUsahaID', 'vouching_bu_beban_usaha_idx');
        });

        DB::statement(
            'ALTER TABLE `vouching_bu` '
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
        Schema::dropIfExists('vouching_bu');
    }
};
