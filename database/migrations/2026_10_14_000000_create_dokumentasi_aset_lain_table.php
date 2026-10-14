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
        Schema::create('dokumentasi_aset_lain', function (Blueprint $table) {
            $table->id('DokumentasiID');

            $table->foreignId('AsetLainID')
                ->constrained('aset_lain', 'AsetLainID')
                ->cascadeOnDelete();

            $table->string('NomorPerjanjian');
            $table->string('Objek');
            $table->date('TanggalPerjanjian');
            $table->date('TanggalMulai');
            $table->date('TanggalAkhir');

            $table->decimal('Nominal', 18, 2)->default(0);
            $table->integer('JangkaWaktu')->default(0);
            $table->integer('MasaPemakaian')->default(0);

            $table->decimal('AmortisasiBulan', 18, 2)->default(0);
            $table->decimal('AmortisasiAkhirTahun', 18, 2)->default(0);
            $table->decimal('Audit', 18, 2)->default(0);
            $table->decimal('Perusahaan', 18, 2)->default(0);
            $table->decimal('Selisih', 18, 2)->default(0);

            $table->timestamps();

            $table->index('AsetLainID', 'dokumentasi_aset_lain_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_aset_lain');
    }
};

