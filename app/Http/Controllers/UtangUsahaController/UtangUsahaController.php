<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UtangUsahaController extends Controller
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

        $utangUsaha = UtangUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'UtangUsahaID' => $utangUsaha->UtangUsahaID,
                'JwbKasusID' => $utangUsaha->JwbKasusID,
                'ProsedurCheck' => $utangUsaha->ProsedurCheck,
                'DokumenCheck' => $utangUsaha->DokumenCheck,
                'KonfirmasiCheck' => $utangUsaha->KonfirmasiCheck,
                'RekapCheck' => $utangUsaha->RekapCheck,
                'JurnalCheck' => $utangUsaha->JurnalCheck,
                'RekonsiliasiCheck' => $utangUsaha->RekonsiliasiCheck,
                'ProsedurAltCheck' => $utangUsaha->ProsedurAltCheck,
                'Kesimpulan' => $utangUsaha->Kesimpulan,
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
            'JurnalCheck' => [
                'sometimes',
                'boolean',
            ],
            'RekonsiliasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'ProsedurAltCheck' => [
                'sometimes',
                'boolean',
            ],
            'Kesimpulan' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $utangUsaha = UtangUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $utangUsaha->fill($validated);
        $utangUsaha->save();

        return response()->json([
            'success' => true,
            'data' => [
                'UtangUsahaID' => $utangUsaha->UtangUsahaID,
                'JwbKasusID' => $utangUsaha->JwbKasusID,
                'ProsedurCheck' => $utangUsaha->ProsedurCheck,
                'DokumenCheck' => $utangUsaha->DokumenCheck,
                'KonfirmasiCheck' => $utangUsaha->KonfirmasiCheck,
                'RekapCheck' => $utangUsaha->RekapCheck,
                'JurnalCheck' => $utangUsaha->JurnalCheck,
                'RekonsiliasiCheck' => $utangUsaha->RekonsiliasiCheck,
                'ProsedurAltCheck' => $utangUsaha->ProsedurAltCheck,
                'Kesimpulan' => $utangUsaha->Kesimpulan,
            ],
        ]);
    }
}