<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\AsetTetap\AsetTetap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsetTetapController extends Controller
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

        $asetTetap = AsetTetap::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'AsetTetapID' => $asetTetap->AsetTetapID,
                'JwbKasusID' => $asetTetap->JwbKasusID,
                'ProsedurCheck' => $asetTetap->ProsedurCheck,
                'DokumenCheck' => $asetTetap->DokumenCheck,
                'AsetLamaCheck' => $asetTetap->AsetLamaCheck,
                'AsetBaruCheck' => $asetTetap->AsetBaruCheck,
                'UjiPenyusutanCheck' => $asetTetap->UjiPenyusutanCheck,
                'JurnalCheck' => $asetTetap->JurnalCheck,
                'Kesimpulan' => $asetTetap->Kesimpulan,
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
            'AsetLamaCheck' => [
                'sometimes',
                'boolean',
            ],
            'AsetBaruCheck' => [
                'sometimes',
                'boolean',
            ],
            'UjiPenyusutanCheck' => [
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

        $asetTetap = AsetTetap::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $asetTetap->fill($validated);
        $asetTetap->save();

        return response()->json([
            'success' => true,
            'data' => [
                'AsetTetapID' => $asetTetap->AsetTetapID,
                'JwbKasusID' => $asetTetap->JwbKasusID,
                'ProsedurCheck' => $asetTetap->ProsedurCheck,
                'DokumenCheck' => $asetTetap->DokumenCheck,
                'AsetLamaCheck' => $asetTetap->AsetLamaCheck,
                'AsetBaruCheck' => $asetTetap->AsetBaruCheck,
                'UjiPenyusutanCheck' => $asetTetap->UjiPenyusutanCheck,
                'JurnalCheck' => $asetTetap->JurnalCheck,
                'Kesimpulan' => $asetTetap->Kesimpulan,
            ],
        ]);
    }
}