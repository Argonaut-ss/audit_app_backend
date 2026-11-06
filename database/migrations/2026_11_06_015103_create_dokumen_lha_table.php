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
        Schema::create('dokumen_lha', function (Blueprint $table) {
            $table->id('DokumenLHA');
            $table->unsignedBigInteger('LaporanHasilAuditID')->unique();
            $table->string('NamaLHA')->nullable();
            $table->string('TipeLHA')->nullable();
            $table->string('LinkLHA')->nullable();
            $table->string('NamaLAI')->nullable();
            $table->string('TipeLAI')->nullable();
            $table->string('LinkLAI')->nullable();
            $table->string('NamaSRM')->nullable();
            $table->string('TipeSRM')->nullable();
            $table->string('LinkSRM')->nullable();
            $table->string('NamaSPMPJ')->nullable();
            $table->string('TipeSPMPJ')->nullable();
            $table->string('LinkSPMPJ')->nullable();
            $table->string('NamaPJKA')->nullable();
            $table->string('TipePJKA')->nullable();
            $table->string('LinkPJKA')->nullable();
            $table->string('NamaPD')->nullable();
            $table->string('TipePD')->nullable();
            $table->string('LinkPD')->nullable();
            $table->string('NamaPPLKA')->nullable();
            $table->string('TipePPLKA')->nullable();
            $table->string('LinkPPLKA')->nullable();
            $table->string('NamaPLKAPB')->nullable();
            $table->string('TipePLKAPB')->nullable();
            $table->string('LinkPLKAPB')->nullable();

            $table->foreign('LaporanHasilAuditID')
                ->references('LaporanHasilAuditID')
                ->on('laporan_hasil_audit')
                ->cascadeOnDelete();
        });

        DB::statement('
            ALTER TABLE dokumen_lha
            ADD FileLHA MEDIUMBLOB NULL,
            ADD FileLAI MEDIUMBLOB NULL,
            ADD FileSRM MEDIUMBLOB NULL,
            ADD FileSPMPJ MEDIUMBLOB NULL,
            ADD FilePJKA MEDIUMBLOB NULL,
            ADD FilePD MEDIUMBLOB NULL,
            ADD FilePPLKA MEDIUMBLOB NULL,
            ADD FilePLKAPB MEDIUMBLOB NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_lha', function (Blueprint $table) {
            $table->dropForeign(['LaporanHasilAuditID']);
        });

        Schema::dropIfExists('dokumen_lha');
    }
};
