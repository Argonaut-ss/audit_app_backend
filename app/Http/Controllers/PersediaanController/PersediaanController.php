<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Persediaan\Persediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersediaanController extends Controller
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

        $persediaan = Persediaan::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'PersediaanID' => $persediaan->PersediaanID,
                'JwbKasusID' => $persediaan->JwbKasusID,
                'ProsedurCheck' => $persediaan->ProsedurCheck,
                'DokumenCheck' => $persediaan->DokumenCheck,
                'StockCheck' => $persediaan->StockCheck,
                'MutasiStockCheck' => $persediaan->MutasiStockCheck,
                'UjiMutasiCheck' => $persediaan->UjiMutasiCheck,
                'TestPricingCheck' => $persediaan->TestPricingCheck,
                'JurnalCheck' => $persediaan->JurnalCheck,
                'Kesimpulan' => $persediaan->Kesimpulan,
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
            'StockCheck' => [
                'sometimes',
                'boolean',
            ],
            'MutasiStockCheck' => [
                'sometimes',
                'boolean',
            ],
            'UjiMutasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'TestPricingCheck' => [
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

        $persediaan = Persediaan::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $persediaan->fill($validated);
        $persediaan->save();

        return response()->json([
            'success' => true,
            'data' => [
                'PersediaanID' => $persediaan->PersediaanID,
                'JwbKasusID' => $persediaan->JwbKasusID,
                'ProsedurCheck' => $persediaan->ProsedurCheck,
                'DokumenCheck' => $persediaan->DokumenCheck,
                'StockCheck' => $persediaan->StockCheck,
                'MutasiStockCheck' => $persediaan->MutasiStockCheck,
                'UjiMutasiCheck' => $persediaan->UjiMutasiCheck,
                'TestPricingCheck' => $persediaan->TestPricingCheck,
                'JurnalCheck' => $persediaan->JurnalCheck,
                'Kesimpulan' => $persediaan->Kesimpulan,
            ],
        ]);
    }
}