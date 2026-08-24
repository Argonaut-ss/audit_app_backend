<?php

namespace App\Http\Controllers;

use App\Models\DetilVerifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DetilVerifikasiController extends Controller
{
    /*
     * =====================================================
     * INDEX
     * =====================================================
     */
    public function index(Request $request): JsonResponse
    {
        $detail = DetilVerifikasi::with([
            'jwbKasus',
        ])
            ->orderByDesc('ID')
            ->get();

        return response()->json($detail);
    }

    /*
     * =====================================================
     * SHOW
     * =====================================================
     */
    public function show(Request $request, $id)
    {
        $detail = DetilVerifikasi::with([
            'jwbKasus',
        ])->findOrFail($id);

        return response()->json($detail);
    }

    /*
     * =====================================================
     * UPDATE
     * =====================================================
     *
     * Update checklist verifikasi.
     */

    public function update(Request $request, $id)
    {
        $detail = DetilVerifikasi::findOrFail($id);

        $validated = $request->validate([

            'DetailPermintaan' => [
                'nullable',
                'boolean',
            ],

            'RincianPersediaan' => [
                'nullable',
                'boolean',
            ],

            'SPK' => [
                'nullable',
                'boolean',
            ],

            'HargaPersediaan' => [
                'nullable',
                'boolean',
            ],

            'SuratTugasKAP' => [
                'nullable',
                'boolean',
            ],

            'StokOpname' => [
                'nullable',
                'boolean',
            ],

            'PenugasanKlien' => [
                'nullable',
                'boolean',
            ],

            'MutasiPersediaan' => [
                'nullable',
                'boolean',
            ],

            'PernyataanLKA' => [
                'nullable',
                'boolean',
            ],

            'MutasiAset' => [
                'nullable',
                'boolean',
            ],

            'PernyataanPMPJ' => [
                'nullable',
                'boolean',
            ],

            'ObservasiAsset' => [
                'nullable',
                'boolean',
            ],

            'RepresentationLetter' => [
                'nullable',
                'boolean',
            ],

            'KepemilikanAset' => [
                'nullable',
                'boolean',
            ],

            'AktaPendirian' => [
                'nullable',
                'boolean',
            ],

            'PenjualanAset' => [
                'nullable',
                'boolean',
            ],

            'SKKPendirian' => [
                'nullable',
                'boolean',
            ],

            'AsetLain' => [
                'nullable',
                'boolean',
            ],

            'AktaPerubahan' => [
                'nullable',
                'boolean',
            ],

            'DokumenSewa' => [
                'nullable',
                'boolean',
            ],

            'SKKPerubahan' => [
                'nullable',
                'boolean',
            ],

            'PolisAssurance' => [
                'nullable',
                'boolean',
            ],

            'SIUP' => [
                'nullable',
                'boolean',
            ],

            'UtangUsaha' => [
                'nullable',
                'boolean',
            ],

            'NIB' => [
                'nullable',
                'boolean',
            ],

            'AKiUtang' => [
                'nullable',
                'boolean',
            ],

            'NPWPPerusahaan' => [
                'nullable',
                'boolean',
            ],

            'KonfirmasiUtang' => [
                'nullable',
                'boolean',
            ],

            'NPWPDirektur' => [
                'nullable',
                'boolean',
            ],

            'PelunasanUtang' => [
                'nullable',
                'boolean',
            ],

            'StrukturOrganisasi' => [
                'nullable',
                'boolean',
            ],

            'RekeningUtangBank' => [
                'nullable',
                'boolean',
            ],

            'RUPS' => [
                'nullable',
                'boolean',
            ],

            'KonfirmasiBank' => [
                'nullable',
                'boolean',
            ],

            'AuditSebelumnya' => [
                'nullable',
                'boolean',
            ],

            'PembayaranPajak' => [
                'nullable',
                'boolean',
            ],

            'LaporanKeuangan' => [
                'nullable',
                'boolean',
            ],

            'UtangLain' => [
                'nullable',
                'boolean',
            ],

            'BukuBesar' => [
                'nullable',
                'boolean',
            ],

            'PenambahanModal' => [
                'nullable',
                'boolean',
            ],

            'CashCount' => [
                'nullable',
                'boolean',
            ],

            'PenarikanModal' => [
                'nullable',
                'boolean',
            ],

            'MutasiKas' => [
                'nullable',
                'boolean',
            ],

            'PembagianModal' => [
                'nullable',
                'boolean',
            ],

            'RekeningKoranBank' => [
                'nullable',
                'boolean',
            ],

            'PembagianDeviden' => [
                'nullable',
                'boolean',
            ],

            'AKBank' => [
                'nullable',
                'boolean',
            ],

            'SamplingPendapatan' => [
                'nullable',
                'boolean',
            ],

            'RincianPiutang' => [
                'nullable',
                'boolean',
            ],

            'SamplingBeban' => [
                'nullable',
                'boolean',
            ],

            'AKPiutang' => [
                'nullable',
                'boolean',
            ],

            'SPTBadanSebelumnya' => [
                'nullable',
                'boolean',
            ],

            'KonfirmasiPiutang' => [
                'nullable',
                'boolean',
            ],

            'PerhitunganPajakBadan' => [
                'nullable',
                'boolean',
            ],

            'PelunasanPiutang' => [
                'nullable',
                'boolean',
            ],

            'SPTDanSPP' => [
                'nullable',
                'boolean',
            ],
        ]);
        $detail->update($validated);

        return response()->json([
            'message' => 'Detail verifikasi berhasil diperbarui.',
            'data' => $detail->fresh(),
        ]);
    }
}