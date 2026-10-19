<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\SetaraKas\SetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetaraKasController extends Controller
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

        $setaraKas = SetaraKas::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'SetaraKasID' => $setaraKas->SetaraKasID,
                'JwbKasusID' => $setaraKas->JwbKasusID,
                'ProsedurCheck' => $setaraKas->ProsedurCheck,
                'DokumenCheck' => $setaraKas->DokumenCheck,
                'RekeningCheck' => $setaraKas->RekeningCheck,
                'CekSaldoCheck' => $setaraKas->CekSaldoCheck,
                'RekonsilasiCheck' => $setaraKas->RekonsilasiCheck,
                'KonfirmasiCheck' => $setaraKas->KonfirmasiCheck,
                'RekapCheck' => $setaraKas->RekapCheck,
                'JurnalCheck' => $setaraKas->JurnalCheck,
                'Kesimpulan' => $setaraKas->Kesimpulan,
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
            'RekeningCheck' => [
                'sometimes',
                'boolean',
            ],
            'CekSaldoCheck' => [
                'sometimes',
                'boolean',
            ],
            'RekonsilasiCheck' => [
                'sometimes',
                'boolean',
            ],
            'JurnalCheck' => [
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
            'Kesimpulan' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $setaraKas = SetaraKas::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        $setaraKas->fill($validated);
        $setaraKas->save();

        return response()->json([
            'success' => true,
            'data' => [
                'SetaraKasID' => $setaraKas->SetaraKasID,
                'JwbKasusID' => $setaraKas->JwbKasusID,
                'ProsedurCheck' => $setaraKas->ProsedurCheck,
                'DokumenCheck' => $setaraKas->DokumenCheck,
                'KonfirmasiCheck' => $setaraKas->KonfirmasiCheck,
                'RekapCheck' => $setaraKas->RekapCheck,
                'JurnalCheck' => $setaraKas->JurnalCheck,
                'RekonsiliasiCheck' => $setaraKas->RekonsiliasiCheck,
                'ProsedurAltCheck' => $setaraKas->ProsedurAltCheck,
                'Kesimpulan' => $setaraKas->Kesimpulan,
            ],
        ]);
    }
}