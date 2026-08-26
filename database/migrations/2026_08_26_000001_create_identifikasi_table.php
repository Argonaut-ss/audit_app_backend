<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifikasi', function (Blueprint $table) {
            $table->id('IdentifikasiID');
            $table->foreignId('JwbKasusID')->constrained('jwb_kasus', 'JwbKasusID')->cascadeOnDelete();
            
            $table->year('Tahun')->nullable();
            $table->string('OpiniAudit')->nullable();
            $table->string('NoSuratPengesahan')->nullable();
            $table->string('LaporanSPT')->nullable();
            $table->string('NoSuratKeputusan')->nullable();
            $table->string('LaporanKeuangan')->nullable();
            $table->string('TipePerikatan')->nullable();
            $table->string('SumberDana')->nullable();
            $table->string('JenisPerikatan')->nullable();
            $table->string('TujuanTransaksi')->nullable();
            $table->string('StandardAkutansi')->nullable();
            $table->unsignedBigInteger('TotalAset')->nullable();
            $table->string('NamaKAP')->nullable();
            $table->unsignedBigInteger('Pendapatan')->nullable();
            $table->bigInteger('LabaRugi')->nullable();
            
            // Kontak Klien
            $table->string('KontakNama')->nullable();
            $table->string('KontakJabatan')->nullable();
            $table->string('KontakNomor')->nullable();
            $table->string('KontakEmail')->nullable();
            
            // Dokumen Pendukung
            $table->string('FileAkte')->nullable();
            $table->string('FileNPWP')->nullable();
            $table->string('FileStrukturOrg')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identifikasi');
    }
};