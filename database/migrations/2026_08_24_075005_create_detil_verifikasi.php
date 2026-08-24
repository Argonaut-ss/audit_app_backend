<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detil_verifikasi', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('JwbKasusID');
            $table->boolean('DetailPermintaan')->default(false);
            $table->boolean('RincianPersediaan')->default(false);
            $table->boolean('SPK')->default(false);
            $table->boolean('HargaPersediaan')->default(false);
            $table->boolean('SuratTugasKAP')->default(false);
            $table->boolean('StokOpname')->default(false);
            $table->boolean('PenugasanKlien')->default(false);
            $table->boolean('MutasiPersediaan')->default(false);
            $table->boolean('PernyataanLKA')->default(false);
            $table->boolean('MutasiAset')->default(false);
            $table->boolean('PernyataanPMPJ')->default(false);
            $table->boolean('ObservasiAsset')->default(false);
            $table->boolean('RepresentationLetter')->default(false);
            $table->boolean('KepemilikanAset')->default(false);
            $table->boolean('AktaPendirian')->default(false);
            $table->boolean('PenjualanAset')->default(false);
            $table->boolean('SKKPendirian')->default(false);
            $table->boolean('AsetLain')->default(false);
            $table->boolean('AktaPerubahan')->default(false);
            $table->boolean('DokumenSewa')->default(false);
            $table->boolean('SKKPerubahan')->default(false);
            $table->boolean('PolisAssurance')->default(false);
            $table->boolean('SIUP')->default(false);
            $table->boolean('UtangUsaha')->default(false);
            $table->boolean('NIB')->default(false);
            $table->boolean('AKiUtang')->default(false);
            $table->boolean('NPWPPerusahaan')->default(false);
            $table->boolean('KonfirmasiUtang')->default(false);
            $table->boolean('NPWPDirektur')->default(false);
            $table->boolean('PelunasanUtang')->default(false);
            $table->boolean('StrukturOrganisasi')->default(false);
            $table->boolean('RekeningUtangBank')->default(false);
            $table->boolean('RUPS')->default(false);
            $table->boolean('KonfirmasiBank')->default(false);
            $table->boolean('AuditSebelumnya')->default(false);
            $table->boolean('PembayaranPajak')->default(false);
            $table->boolean('LaporanKeuangan')->default(false);
            $table->boolean('UtangLain')->default(false);
            $table->boolean('BukuBesar')->default(false);
            $table->boolean('PenambahanModal')->default(false);
            $table->boolean('CashCount')->default(false);
            $table->boolean('PenarikanModal')->default(false);
            $table->boolean('MutasiKas')->default(false);
            $table->boolean('PembagianModal')->default(false);
            $table->boolean('RekeningKoranBank')->default(false);
            $table->boolean('PembagianDeviden')->default(false);
            $table->boolean('AKBank')->default(false);
            $table->boolean('SamplingPendapatan')->default(false);
            $table->boolean('RincianPiutang')->default(false);
            $table->boolean('SamplingBeban')->default(false);
            $table->boolean('AKPiutang')->default(false);
            $table->boolean('SPTBadanSebelumnya')->default(false);
            $table->boolean('KonfirmasiPiutang')->default(false);
            $table->boolean('PerhitunganPajakBadan')->default(false);
            $table->boolean('PelunasanPiutang')->default(false);
            $table->boolean('SPTDanSPP')->default(false);

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();

            $table->unique(
                'JwbKasusID',
                'detil_verifikasi_jwb_kasus_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('detil_verifikasi', function (Blueprint $table) {
            $table->dropForeign([
                'JwbKasusID',
            ]);
        });

        Schema::dropIfExists('detil_verifikasi');
    }
};