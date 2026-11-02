<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Services\KertasKerjaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KertasKerjaController extends Controller
{
    public function show(Request $request, int $jwbKasusId, KertasKerjaService $kertasKerjaService): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();

        $result = $kertasKerjaService->getKertasKerja($jwbKasus);
        $periode = $jwbKasus->Periode ? Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        return response()->json([
            'status_code' => 200,
            'message' => 'Data Kertas Kerja berhasil diambil.',
            'data' => [
                'wpl' => $result['wpl'],
                'wbs' => $result['wbs'],
                'periode' => $periode,
            ],
            'settings' => [],
        ]);
    }
}

