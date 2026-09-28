<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendapatanUsahaController extends Controller
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

        $pendapatanUsaha = PendapatanUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'PendapatanUsahaID' => $pendapatanUsaha->PendapatanUsahaID,
                'JwbKasusID' => $pendapatanUsaha->JwbKasusID,
                'ProsedurCheck' => $pendapatanUsaha->ProsedurCheck,
                'DokumenCheck' => $pendapatanUsaha->DokumenCheck,
                'CutOffCheck' => $pendapatanUsaha->CutOffCheck,
                'VouchingCheck' => $pendapatanUsaha->VouchingCheck,
                'JurnalCheck' => $pendapatanUsaha->JurnalCheck,
                'Kesimpulan' => $pendapatanUsaha->Kesimpulan,
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

        $pendapatanUsaha = PendapatanUsaha::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $pendapatanUsaha->fill($validated);
        $pendapatanUsaha->save();

        return response()->json([
            'success' => true,
            'data' => [
                'PendapatanUsahaID' => $pendapatanUsaha->PendapatanUsahaID,
                'JwbKasusID' => $pendapatanUsaha->JwbKasusID,
                'ProsedurCheck' => $pendapatanUsaha->ProsedurCheck,
                'DokumenCheck' => $pendapatanUsaha->DokumenCheck,
                'CutOffCheck' => $pendapatanUsaha->CutOffCheck,
                'VouchingCheck' => $pendapatanUsaha->VouchingCheck,
                'JurnalCheck' => $pendapatanUsaha->JurnalCheck,
                'Kesimpulan' => $pendapatanUsaha->Kesimpulan,
            ],
        ]);
    }
}