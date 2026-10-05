<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Kas\Kas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KasController extends Controller
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

        $kas = Kas::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'KasID' => $kas->KasID,
                'JwbKasusID' => $kas->JwbKasusID,
                'ProsedurCheck' => $kas->ProsedurCheck,
                'DokumenCheck' => $kas->DokumenCheck,
                'CashCountCheck' => $kas->CashCountCheck,
                'RekapMutasiCheck' => $kas->RekapMutasiCheck,
                'UjiMutasiCheck' => $kas->UjiMutasiCheck,
                'JurnalCheck' => $kas->JurnalCheck,
                'Kesimpulan' => $kas->Kesimpulan,
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
            'CashCountCheck' => [
                'sometimes',
                'boolean',
            ],
            'RekapMutasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'UjiMutasiCheck' => [
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

        $kas = Kas::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $kas->fill($validated);
        $kas->save();

        return response()->json([
            'success' => true,
            'data' => [
                'KasID' => $kas->KasID,
                'JwbKasusID' => $kas->JwbKasusID,
                'ProsedurCheck' => $kas->ProsedurCheck,
                'DokumenCheck' => $kas->DokumenCheck,
                'CashCountCheck' => $kas->CashCountCheck,
                'RekapMutasiCheck' => $kas->RekapMutasiCheck,
                'UjiMutasiCheck' => $kas->UjiMutasiCheck,
                'JurnalCheck' => $kas->JurnalCheck,
                'Kesimpulan' => $kas->Kesimpulan,
            ],
        ]);
    }
}