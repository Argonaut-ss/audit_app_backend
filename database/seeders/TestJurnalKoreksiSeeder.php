<?php

namespace Database\Seeders;

use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\Kas\Kas;
use App\Models\Kas\JurnalKoreksiKas;
use App\Models\Kas\PembayaranJurnalKoreksiKas;
use App\Models\SetaraKas\SetaraKas;
use App\Models\SetaraKas\JurnalKoreksiSetaraKas;
use App\Models\SetaraKas\PembayaranJurnalKoreksiSetaraKas;
use App\Models\Piutang\Piutang;
use App\Models\Piutang\JurnalKoreksi as JurnalKoreksiPiutang;
use App\Models\Piutang\PembayaranJurnalKoreksi as PembayaranJurnalKoreksiPiutang;
use App\Models\Persediaan\Persediaan;
use App\Models\Persediaan\JurnalKoreksiPersediaan;
use App\Models\Persediaan\PembayaranJurnalKoreksiPersediaan;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\JurnalKoreksiAsetLain;
use App\Models\AsetLain\PembayaranJurnalKoreksiAsetLain;
use App\Models\UtangUsaha\UtangUsaha;
use App\Models\UtangUsaha\JurnalKoreksiUtangUsaha;
use App\Models\UtangUsaha\PembayaranJurnalKoreksiUtangUsaha;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestJurnalKoreksiSeeder extends Seeder
{
    /**
     * Seeder data riil Jurnal Koreksi dari 9 Pengujian Substantif (Benchmark Web Authen).
     */
    public function run(?int $targetJwbKasusId = null): void
    {
        $jwbKasusId = $targetJwbKasusId ?? JwbKasus::value('JwbKasusID') ?? 1;

        DB::transaction(function () use ($jwbKasusId) {
            // 1. Pastikan COA tambahan dari benchmark tersedia
            $additionalCoas = [
                [
                    'NoAkun' => '5-3400',
                    'NamaAkun' => 'Beban Kerugian Persediaan',
                    'Saldo' => 'Debit',
                    'MappingTop' => 'Pendapatan (Beban) Lain-lain',
                    'SubMappingTop' => 'Beban Lain-Lain',
                    'PerBook' => 0,
                    'AuditSebelum' => 0,
                ],
                [
                    'NoAkun' => '2-2200',
                    'NamaAkun' => 'Utang PPh 4 ayat 2',
                    'Saldo' => 'Kredit',
                    'MappingTop' => 'Utang Pajak',
                    'SubMappingTop' => 'Utang Pajak',
                    'PerBook' => 0,
                    'AuditSebelum' => 0,
                ],
                [
                    'NoAkun' => '5-3500',
                    'NamaAkun' => 'Beban PPH 4 Ayat 2',
                    'Saldo' => 'Debit',
                    'MappingTop' => 'Pendapatan (Beban) Lain-lain',
                    'SubMappingTop' => 'Beban Lain-Lain',
                    'PerBook' => 0,
                    'AuditSebelum' => 0,
                ],
            ];

            foreach ($additionalCoas as $coaData) {
                COA::updateOrCreate(
                    ['JwbKasusID' => $jwbKasusId, 'NoAkun' => $coaData['NoAkun']],
                    $coaData
                );
            }

            $coaMap = COA::where('JwbKasusID', $jwbKasusId)->pluck('COAID', 'NoAkun');

            // 2. Setup model induk dan bersihkan jurnal koreksi lama
            $kas = Kas::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiKas::where('KasID', $kas->KasID)->delete();

            $setaraKas = SetaraKas::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiSetaraKas::where('SetaraKasID', $setaraKas->SetaraKasID)->delete();

            $piutang = Piutang::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiPiutang::where('PiutangID', $piutang->PiutangID)->delete();

            $persediaan = Persediaan::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiPersediaan::where('PersediaanID', $persediaan->PersediaanID)->delete();

            $asetLain = AsetLain::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiAsetLain::where('AsetLainID', $asetLain->AsetLainID)->delete();

            $utangUsaha = UtangUsaha::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
            JurnalKoreksiUtangUsaha::where('UtangUsahaID', $utangUsaha->UtangUsahaID)->delete();

            // Helper closures
            $addKas = function (string $ket, array $items) use ($kas, $coaMap) {
                $h = JurnalKoreksiKas::create(['KasID' => $kas->KasID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiKas::create([
                        'JurnalKoreksiKasID' => $h->JurnalKoreksiKasID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            $addSetaraKas = function (string $ket, array $items) use ($setaraKas, $coaMap) {
                $h = JurnalKoreksiSetaraKas::create(['SetaraKasID' => $setaraKas->SetaraKasID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiSetaraKas::create([
                        'JurnalKoreksiSetaraKasID' => $h->JurnalKoreksiSetaraKasID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            $addPiutang = function (string $ket, array $items) use ($piutang, $coaMap) {
                $h = JurnalKoreksiPiutang::create(['PiutangID' => $piutang->PiutangID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiPiutang::create([
                        'JurnalKoreksiID' => $h->JurnalKoreksiID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            $addPersediaan = function (string $ket, array $items) use ($persediaan, $coaMap) {
                $h = JurnalKoreksiPersediaan::create(['PersediaanID' => $persediaan->PersediaanID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiPersediaan::create([
                        'JurnalKoreksiPersediaanID' => $h->JurnalKoreksiPersediaanID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            $addAsetLain = function (string $ket, array $items) use ($asetLain, $coaMap) {
                $h = JurnalKoreksiAsetLain::create(['AsetLainID' => $asetLain->AsetLainID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiAsetLain::create([
                        'JurnalKoreksiAsetLainID' => $h->JurnalKoreksiAsetLainID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            $addUtang = function (string $ket, array $items) use ($utangUsaha, $coaMap) {
                $h = JurnalKoreksiUtangUsaha::create(['UtangUsahaID' => $utangUsaha->UtangUsahaID, 'Keterangan' => $ket]);
                foreach ($items as $it) {
                    PembayaranJurnalKoreksiUtangUsaha::create([
                        'JurnalKoreksiUtangUsahaID' => $h->JurnalKoreksiUtangUsahaID,
                        'COAID' => $coaMap[$it[0]] ?? null,
                        'Debet' => $it[1],
                        'Kredit' => $it[2],
                    ]);
                }
            };

            // 1. KAS (3 Jurnal)
            $addKas('Koreksi Atas', [
                ['5-3200', 1000000, 0],
                ['1-1111', 0, 1000000],
            ]);
            $addKas('Koreksi Atas', [
                ['1-0000', 10000000, 0],
                ['1-1410', 0, 10000000],
            ]);
            $addKas('Koreksi Atas', [
                ['1-1410', 410000, 0],
                ['1-1120', 0, 410000],
            ]);

            // 2. SETARA KAS (2 Jurnal)
            $addSetaraKas('Koreksi Atas', [
                ['5-2100', 295000000, 0],
                ['1-1111', 0, 295000000],
            ]);
            // Catatan: Mengikuti kondisi authen asli (hanya debit peralatan)
            $addSetaraKas('Koreksi Atas', [
                ['1-2141', 4000000, 0],
            ]);

            // 3. PIUTANG USAHA (9 Jurnal)
            $addPiutang('Koreksi Atas', [
                ['1-1111', 53170000, 0],
                ['1-1210', 0, 53170000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1111', 45391000, 0],
                ['1-1210', 0, 45391000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['5-2300', 879664324, 0],
                ['1-1220', 0, 879664324],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1220', 20000000, 0],
                ['1-1210', 0, 20000000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1300', 743750000, 0],
                ['5-1100', 0, 743750000],
                ['4-1100', 700000000, 0],
                ['1-1210', 0, 700000000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1300', 608340000, 0],
                ['5-1100', 0, 608340000],
                ['4-1100', 567900000, 0],
                ['1-1210', 0, 567900000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1300', 223500000, 0],
                ['5-1100', 0, 223500000],
                ['4-1100', 226560000, 0],
                ['1-1210', 0, 226560000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1300', 482800000, 0],
                ['5-1100', 0, 482800000],
                ['4-1100', 965670000, 0],
                ['1-1210', 0, 965670000],
            ]);
            $addPiutang('Koreksi Atas', [
                ['1-1111', 95068500, 0],
                ['1-1210', 0, 95068500],
            ]);

            // 4. PERSEDIAAN (8 Jurnal)
            $addPersediaan('Koreksi Atas', [
                ['1-1111', 1230, 0],
                ['1-0000', 0, 1230],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-3300', 1760000, 0],
                ['1-1300', 0, 1760000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-2700', 1600000, 0],
                ['1-1300', 0, 1600000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-2700', 1700000, 0],
                ['1-1300', 0, 1700000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-2700', 1740000, 0],
                ['1-1300', 0, 1740000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-3400', 1690000, 0],
                ['1-1300', 0, 1690000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-3400', 4250000, 0],
                ['1-1300', 0, 4250000],
            ]);
            $addPersediaan('Koreksi Atas', [
                ['5-3400', 3520000, 0],
                ['1-1300', 0, 3520000],
            ]);

            // 5. ASET LAIN-LAIN (4 Jurnal)
            $addAsetLain('Koreksi Atas', [
                ['5-2400', 13151359, 0],
                ['1-1410', 0, 13151359],
            ]);
            $addAsetLain('Koreksi Atas', [
                ['1-1410', 320000, 0],
                ['1-1120', 0, 320000],
            ]);
            $addAsetLain('Koreksi Atas', [
                ['5-3500', 23500000, 0],
                ['2-2200', 0, 23500000],
            ]);
            $addAsetLain('Koreksi Atas', [
                ['5-3500', 291068498, 0],
                ['2-2200', 0, 291068498],
            ]);

            // 6. UTANG USAHA (4 Jurnal)
            $addUtang('Koreksi Atas', [
                ['2-1100', 40000000, 0],
                ['1-1113', 0, 40000000],
            ]);
            $addUtang('Koreksi Atas', [
                ['2-1100', 51500000, 0],
                ['1-1112', 0, 51500000],
            ]);
            $addUtang('Koreksi Atas', [
                ['2-1100', 18000000, 0],
                ['1-1113', 0, 18000000],
            ]);
            $addUtang('Koreksi Atas', [
                ['2-1100', 53000000, 0],
                ['1-1111', 0, 53000000],
            ]);
        });
    }
}
