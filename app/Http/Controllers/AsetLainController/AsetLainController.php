<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\AsetLain\AsetLain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsetLainController extends Controller
{
    public function show(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->first();

        if (!$jwbKasus) {
            return response()->json([
                'success' => false,
                'message' => 'Data kasus tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        $asetLain = AsetLain::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'AsetLainID' => $asetLain->AsetLainID,
                'JwbKasusID' => $asetLain->JwbKasusID,
                'ProsedurCheck' => $asetLain->ProsedurCheck,
                'DokumenCheck' => $asetLain->DokumenCheck,
                'KonfirmasiCheck' => $asetLain->KonfirmasiCheck,
                'RekapCheck' => $asetLain->RekapCheck,
                'DokumentasiCheck' => $asetLain->DokumentasiCheck,
                'DokumentasiAsetLainLainCheck' => $asetLain->DokumentasiCheck,
                'BuktiPendukungCheck' => $asetLain->BuktiPendukungCheck,
                'WawancaraCheck' => $asetLain->WawancaraCheck,
                'JurnalCheck' => $asetLain->JurnalCheck,
                'Kesimpulan' => $asetLain->Kesimpulan,
            ],
        ]);
    }

    public function update(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->first();

        if (!$jwbKasus) {
            return response()->json([
                'success' => false,
                'message' => 'Data kasus tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        $validated = $request->validate([
            'ProsedurCheck' => [
                'sometimes',
                'boolean',
            ],
            'DokumenCheck' => [
                'sometimes',
                'boolean',
            ],
            'KonfirmasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'RekapCheck' => [
                'sometimes',
                'boolean',
            ],
            'DokumentasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'DokumentasiAsetLainLainCheck' => [
                'sometimes',
                'boolean',
            ],
            'BuktiPendukungCheck' => [
                'sometimes',
                'boolean',
            ],
            'WawancaraCheck' => [
                'sometimes',
                'boolean',
            ],
            'JurnalCheck' => [
                'sometimes',
                'boolean',
            ],
            'Kesimpulan' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        if (array_key_exists('DokumentasiAsetLainLainCheck', $validated) && !array_key_exists('DokumentasiCheck', $validated)) {
            $validated['DokumentasiCheck'] = $validated['DokumentasiAsetLainLainCheck'];
        }

        $asetLain = AsetLain::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $asetLain->fill($validated);
        $asetLain->save();

        return response()->json([
            'success' => true,
            'data' => [
                'AsetLainID' => $asetLain->AsetLainID,
                'JwbKasusID' => $asetLain->JwbKasusID,
                'ProsedurCheck' => $asetLain->ProsedurCheck,
                'DokumenCheck' => $asetLain->DokumenCheck,
                'KonfirmasiCheck' => $asetLain->KonfirmasiCheck,
                'RekapCheck' => $asetLain->RekapCheck,
                'DokumentasiCheck' => $asetLain->DokumentasiCheck,
                'DokumentasiAsetLainLainCheck' => $asetLain->DokumentasiCheck,
                'BuktiPendukungCheck' => $asetLain->BuktiPendukungCheck,
                'WawancaraCheck' => $asetLain->WawancaraCheck,
                'JurnalCheck' => $asetLain->JurnalCheck,
                'Kesimpulan' => $asetLain->Kesimpulan,
            ],
        ]);
    }
}