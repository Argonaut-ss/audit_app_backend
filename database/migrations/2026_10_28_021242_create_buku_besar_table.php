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
        Schema::create('buku_besar', function (Blueprint $table) {
            $table->id('BukuBesarID');
            $table->unsignedBigInteger('JwbKasusID');
            $table->string('Mapping');
            $table->date('TanggalPerBook')->nullable();
            $table->date('TanggalAuditJln')->nullable();
            $table->date('TanggalAuditSblm')->nullable();

            $table->unique(
                ['JwbKasusID', 'Mapping'],
                'buku_besar_jwb_kasus_mapping_unique'
            );

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buku_besar');
    }
};
