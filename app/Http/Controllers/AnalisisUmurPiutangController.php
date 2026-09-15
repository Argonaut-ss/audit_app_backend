<?php

namespace App\Http\Controllers;

use App\Models\AnalisisUmur;
use App\Models\HasilAnalisisUmur;
use App\Models\JwbKasus;
use App\Models\Piutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisisUmurPiutangController extends Controller
{
    protected function resolveAuthorizedPiutang(Request $request, int $piutangId): Piutang
    {
        $piutang = Piutang::findOrFail($piutangId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        return $piutang;
    }

    protected function buildResponseData(Piutang $piutang): array
    {
        $analisisUmur = $piutang->analisisUmur()
            ->with('hasilAnalisisUmur')
            ->first();

        if (! $analisisUmur) {
            return [
                'rows' => [],
                'SaldoAuditor' => 0,
                'SaldoBB' => 0,
                'Selisih' => 0,
            ];
        }

        return [
            'rows' => $analisisUmur->hasilAnalisisUmur
                ->sortBy('HasilAnalisisUmurID')
                ->map(fn (HasilAnalisisUmur $item) => [
                    'HasilAnalisisUmurID' => $item->HasilAnalisisUmurID,
                    'KelompokUmur' => $item->KelompokUmur,
                    'Jumlah' => $item->Jumlah,
                    'Kerugian' => $item->Kerugian,
                    'CadanganKerugian' => (int) round($item->Jumlah * $item->Kerugian / 100),
                ])
                ->values()
                ->all(),
            'SaldoAuditor' => $analisisUmur->SaldoAuditor,
            'SaldoBB' => $analisisUmur->SaldoBB,
            'Selisih' => $analisisUmur->Selisih,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);

        return response()->json([
            'success' => true,
            'data' => $this->buildResponseData($piutang),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.KelompokUmur' => ['required', 'string', 'distinct', 'in:1-30,31-60,61-90,>90'],
            'rows.*.Jumlah' => ['required', 'integer', 'min:0'],
            'rows.*.Kerugian' => ['required', 'integer', 'min:0', 'max:100'],
            'SaldoBB' => ['required', 'integer'],
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);
        $rows = $validated['rows'];
        $saldoBB = $validated['SaldoBB'];
        $saldoAuditor = (int) array_sum(array_map(
            fn (array $row) => round($row['Jumlah'] * $row['Kerugian'] / 100),
            $rows
        ));

        DB::transaction(function () use ($piutang, $rows, $saldoBB, $saldoAuditor): void {
            $analisisUmur = AnalisisUmur::updateOrCreate(
                ['PiutangID' => $piutang->PiutangID],
                [
                    'SaldoAuditor' => $saldoAuditor,
                    'SaldoBB' => $saldoBB,
                    'Selisih' => $saldoAuditor - $saldoBB,
                ]
            );

            $analisisUmur->hasilAnalisisUmur()->delete();
            $analisisUmur->hasilAnalisisUmur()->createMany($rows);
            $piutang->updateQuietly(['UmurCheck' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data analisis umur piutang berhasil disimpan.',
            'data' => $this->buildResponseData($piutang->fresh()),
        ], 201);
    }
}
