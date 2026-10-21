<?php

namespace App\Http\Controllers;

use App\Models\AsetLain\AsetLain;
use App\Models\AsetTetap\AsetTetap;
use App\Models\BebanUsaha\BebanUsaha;
use App\Models\JwbKasus;
use App\Models\Kas\Kas;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use App\Models\Persediaan\Persediaan;
use App\Models\Piutang\Piutang;
use App\Models\SetaraKas\SetaraKas;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengujianSubstantifController extends Controller
{
    /**
     * Mengambil status kelengkapan/checklist seluruh 9 bagian Pengujian Substantif
     * dalam satu panggilan HTTP tunggal untuk performa render frontend yang instan.
     */
    public function show(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->first();

        if (! $jwbKasus) {
            return response()->json([
                'success' => false,
                'message' => 'Data kasus tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        // Ambil record dari ke-9 modul (menggunakan firstOrCreate agar selalu ada)
        $kas = Kas::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $setaraKas = SetaraKas::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $piutang = Piutang::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $persediaan = Persediaan::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $asetTetap = AsetTetap::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $asetLain = AsetLain::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $utangUsaha = UtangUsaha::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $pendapatanUsaha = PendapatanUsaha::firstOrCreate(['JwbKasusID' => $jwbKasusId]);
        $bebanUsaha = BebanUsaha::firstOrCreate(['JwbKasusID' => $jwbKasusId]);

        return response()->json([
            'success' => true,
            'data' => [
                'kas' => [
                    'ProsedurCheck' => (bool) $kas->ProsedurCheck,
                    'DokumenCheck' => (bool) $kas->DokumenCheck,
                    'CashCountCheck' => (bool) $kas->CashCountCheck,
                    'RekapMutasiCheck' => (bool) $kas->RekapMutasiCheck,
                    'UjiMutasiCheck' => (bool) $kas->UjiMutasiCheck,
                    'JurnalCheck' => (bool) $kas->JurnalCheck,
                    'Kesimpulan' => $kas->Kesimpulan,
                ],
                'setara_kas' => [
                    'ProsedurCheck' => (bool) $setaraKas->ProsedurCheck,
                    'DokumenCheck' => (bool) $setaraKas->DokumenCheck,
                    'RekeningCheck' => (bool) $setaraKas->RekeningCheck,
                    'CekSaldoCheck' => (bool) $setaraKas->CekSaldoCheck,
                    'RekonsilasiCheck' => (bool) $setaraKas->RekonsilasiCheck,
                    'RekonsiliasiCheck' => (bool) $setaraKas->RekonsilasiCheck,
                    'RekonsiliasiBankCheck' => (bool) $setaraKas->RekonsilasiCheck,
                    'KonfirmasiCheck' => (bool) $setaraKas->KonfirmasiCheck,
                    'RekapCheck' => (bool) $setaraKas->RekapCheck,
                    'JurnalCheck' => (bool) $setaraKas->JurnalCheck,
                    'Kesimpulan' => $setaraKas->Kesimpulan,
                ],
                'piutang' => [
                    'ProsedurCheck' => (bool) $piutang->ProsedurCheck,
                    'DokumenCheck' => (bool) $piutang->DokumenCheck,
                    'KonfirmasiCheck' => (bool) $piutang->KonfirmasiCheck,
                    'RekapCheck' => (bool) $piutang->RekapCheck,
                    'ProsedurAltCheck' => (bool) $piutang->ProsedurAltCheck,
                    'RekonsiliasiCheck' => (bool) $piutang->RekonsiliasiCheck,
                    'UmurCheck' => (bool) $piutang->UmurCheck,
                    'JurnalCheck' => (bool) $piutang->JurnalCheck,
                    'Kesimpulan' => $piutang->Kesimpulan,
                ],
                'persediaan' => [
                    'ProsedurCheck' => (bool) $persediaan->ProsedurCheck,
                    'DokumenCheck' => (bool) $persediaan->DokumenCheck,
                    'StockCheck' => (bool) $persediaan->StockCheck,
                    'MutasiStockCheck' => (bool) $persediaan->MutasiStockCheck,
                    'UjiMutasiCheck' => (bool) $persediaan->UjiMutasiCheck,
                    'TestPricingCheck' => (bool) $persediaan->TestPricingCheck,
                    'JurnalCheck' => (bool) $persediaan->JurnalCheck,
                    'Kesimpulan' => $persediaan->Kesimpulan,
                ],
                'aset_tetap' => [
                    'ProsedurCheck' => (bool) $asetTetap->ProsedurCheck,
                    'DokumenCheck' => (bool) $asetTetap->DokumenCheck,
                    'AsetLamaCheck' => (bool) $asetTetap->AsetLamaCheck,
                    'AsetBaruCheck' => (bool) $asetTetap->AsetBaruCheck,
                    'UjiPenyusutanCheck' => (bool) $asetTetap->UjiPenyusutanCheck,
                    'JurnalCheck' => (bool) $asetTetap->JurnalCheck,
                    'Kesimpulan' => $asetTetap->Kesimpulan,
                ],
                'aset_lain_lain' => [
                    'ProsedurCheck' => (bool) $asetLain->ProsedurCheck,
                    'DokumenCheck' => (bool) $asetLain->DokumenCheck,
                    'KonfirmasiCheck' => (bool) $asetLain->KonfirmasiCheck,
                    'RekapCheck' => (bool) $asetLain->RekapCheck,
                    'DokumentasiCheck' => (bool) $asetLain->DokumentasiCheck,
                    'BuktiPendukungCheck' => (bool) $asetLain->BuktiPendukungCheck,
                    'WawancaraCheck' => (bool) $asetLain->WawancaraCheck,
                    'JurnalCheck' => (bool) $asetLain->JurnalCheck,
                    'Kesimpulan' => $asetLain->Kesimpulan,
                ],
                'utang_usaha' => [
                    'ProsedurCheck' => (bool) $utangUsaha->ProsedurCheck,
                    'DokumenCheck' => (bool) $utangUsaha->DokumenCheck,
                    'KonfirmasiCheck' => (bool) $utangUsaha->KonfirmasiCheck,
                    'RekapCheck' => (bool) $utangUsaha->RekapCheck,
                    'ProsedurAltCheck' => (bool) $utangUsaha->ProsedurAltCheck,
                    'RekonsiliasiCheck' => (bool) $utangUsaha->RekonsiliasiCheck,
                    'JurnalCheck' => (bool) $utangUsaha->JurnalCheck,
                    'Kesimpulan' => $utangUsaha->Kesimpulan,
                ],
                'pendapatan_usaha' => [
                    'ProsedurCheck' => (bool) $pendapatanUsaha->ProsedurCheck,
                    'DokumenCheck' => (bool) $pendapatanUsaha->DokumenCheck,
                    'CutOffCheck' => (bool) $pendapatanUsaha->CutOffCheck,
                    'VouchingCheck' => (bool) $pendapatanUsaha->VouchingCheck,
                    'JurnalCheck' => (bool) $pendapatanUsaha->JurnalCheck,
                    'Kesimpulan' => $pendapatanUsaha->Kesimpulan,
                ],
                'beban_usaha' => [
                    'ProsedurCheck' => (bool) $bebanUsaha->ProsedurCheck,
                    'DokumenCheck' => (bool) $bebanUsaha->DokumenCheck,
                    'CutOffCheck' => (bool) $bebanUsaha->CutOffCheck,
                    'VouchingCheck' => (bool) $bebanUsaha->VouchingCheck,
                    'JurnalCheck' => (bool) $bebanUsaha->JurnalCheck,
                    'Kesimpulan' => $bebanUsaha->Kesimpulan,
                ],
            ],
        ]);
    }
}

