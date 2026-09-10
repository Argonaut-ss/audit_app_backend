<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Piutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PiutangController extends Controller
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

        $piutang = Piutang::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'PiutangID' => $piutang->PiutangID,
                'JwbKasusID' => $piutang->JwbKasusID,
                'ProsedurCheck' => $piutang->ProsedurCheck,
                'DokumenCheck' => $piutang->DokumenCheck,
                'KonfirmasiCheck' => $piutang->KonfirmasiCheck,
                'RekapCheck' => $piutang->RekapCheck,
                'JurnalCheck' => $piutang->JurnalCheck,
                'RekonsiliasiCheck' => $piutang->RekonsiliasiCheck,
                'UmurCheck' => $piutang->UmurCheck,
                'ProsedurAltCheck' => $piutang->ProsedurAltCheck,
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
            'UmurCheck' => [
                'sometimes',
                'boolean',
            ],
            'ProsedurAltCheck' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $piutang = Piutang::where(
            'JwbKasusID',
            $jwbKasusId
        )->firstOrFail();
        $piutang->fill($validated);
        $piutang->save();

        return response()->json([
            'success' => true,
            'data' => [
                'PiutangID' => $piutang->PiutangID,
                'JwbKasusID' => $piutang->JwbKasusID,
                'ProsedurCheck' => $piutang->ProsedurCheck,
                'DokumenCheck' => $piutang->DokumenCheck,
                'KonfirmasiCheck' => $piutang->KonfirmasiCheck,
                'RekapCheck' => $piutang->RekapCheck,
                'JurnalCheck' => $piutang->JurnalCheck,
                'RekonsiliasiCheck' => $piutang->RekonsiliasiCheck,
                'UmurCheck' => $piutang->UmurCheck,
                'ProsedurAltCheck' => $piutang->ProsedurAltCheck,
            ],
        ]);
    }
}