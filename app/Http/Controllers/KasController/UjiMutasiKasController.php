<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Kas\CashCountKas;
use App\Models\Kas\Kas;
use App\Models\Kas\UjiMutasiKas;
use App\Models\Kas\RekapMutasiKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UjiMutasiKasController extends Controller
{
    private function authorizedSources(Request $request, int $kasId): array
    {
        $kas = Kas::findOrFail($kasId);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $kas->JwbKasusID)
            ->firstOrFail();

        $cashCount = CashCountKas::where('KasID', $kas->KasID)->firstOrFail();
        $rekapMutasi = RekapMutasiKas::where('KasID', $kas->KasID)
            ->orderByDesc('RekapMutasiID')
            ->firstOrFail();

        return [$kas, $cashCount, $rekapMutasi];
    }

    private function serialize(UjiMutasiKas $ujiMutasi): array
    {
        return [
            'UjiMutasiID' => $ujiMutasi->UjiMutasiID,
            'KasID' => $ujiMutasi->KasID,
            'CashCountID' => $ujiMutasi->CashCountID,
            'RekapMutasiID' => $ujiMutasi->RekapMutasiID,
            'Penjelasan' => $ujiMutasi->Penjelasan,
            'created_at' => $ujiMutasi->created_at,
            'updated_at' => $ujiMutasi->updated_at,
        ];
    }

    public function show(Request $request, int $kasId): JsonResponse
    {
        [$kas, $cashCount, $rekapMutasi] = $this->authorizedSources($request, $kasId);
        $ujiMutasi = UjiMutasiKas::where('KasID', $kas->KasID)->first();
        $data = $ujiMutasi
            ? $this->serialize($ujiMutasi)
            : [
                'UjiMutasiID' => null,
                'Penjelasan' => null,
            ];

        $data = array_merge($data, [
            'KasID' => $kas->KasID,
            'CashCountID' => $cashCount->CashCountID,
            'TotalKeseluruhan' => (int) $cashCount->TotalKeseluruhan,
            'RekapMutasiID' => $rekapMutasi->RekapMutasiID,
            'KreditTotal' => (int) $rekapMutasi->KreditTotal,
            'DebitTotal' => (int) $rekapMutasi->DebitTotal,
            'SaldoAwal' => (int) $rekapMutasi->SaldoAwal,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request, int $kasId): JsonResponse
    {
        $validated = $request->validate([
            'Penjelasan' => ['nullable', 'string'],
        ]);
        [$kas, $cashCount, $rekapMutasi] = $this->authorizedSources($request, $kasId);

        $ujiMutasi = DB::transaction(function () use (
            $kas,
            $cashCount,
            $rekapMutasi,
            $validated
        ) {
            return UjiMutasiKas::updateOrCreate(
                ['KasID' => $kas->KasID],
                [
                    'CashCountID' => $cashCount->CashCountID,
                    'RekapMutasiID' => $rekapMutasi->RekapMutasiID,
                    'Penjelasan' => $validated['Penjelasan'] ?? null,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Uji mutasi kas berhasil disimpan.',
            'data' => $this->serialize($ujiMutasi),
        ]);
    }
}
