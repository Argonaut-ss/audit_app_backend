<?php

namespace App\Http\Controllers\BebanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\BebanUsaha\BebanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BebanUsahaController extends Controller
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

        $bebanUsaha = BebanUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'BebanUsahaID' => $bebanUsaha->BebanUsahaID,
                'JwbKasusID' => $bebanUsaha->JwbKasusID,
                'ProsedurCheck' => $bebanUsaha->ProsedurCheck,
                'DokumenCheck' => $bebanUsaha->DokumenCheck,
                'CutOffCheck' => $bebanUsaha->CutOffCheck,
                'VouchingCheck' => $bebanUsaha->VouchingCheck,
                'JurnalCheck' => $bebanUsaha->JurnalCheck,
                'Kesimpulan' => $bebanUsaha->Kesimpulan,
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
            'CutOffCheck' => [
                'sometimes',
                'boolean',
            ],
            'VouchingCheck' => [
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

        $bebanUsaha = BebanUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $bebanUsaha->fill($validated);
        $bebanUsaha->save();

        return response()->json([
            'success' => true,
            'data' => [
                'BebanUsahaID' => $bebanUsaha->BebanUsahaID,
                'JwbKasusID' => $bebanUsaha->JwbKasusID,
                'ProsedurCheck' => $bebanUsaha->ProsedurCheck,
                'DokumenCheck' => $bebanUsaha->DokumenCheck,
                'CutOffCheck' => $bebanUsaha->CutOffCheck,
                'VouchingCheck' => $bebanUsaha->VouchingCheck,
                'JurnalCheck' => $bebanUsaha->JurnalCheck,
                'Kesimpulan' => $bebanUsaha->Kesimpulan,
            ],
        ]);
    }
}