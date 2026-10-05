<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Kas\CashCount;
use App\Models\Kas\Kas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashCountController extends Controller
{
    // Nominal denominasi (hardcoded, tetap) -> nama kolom di tabel cash_count.
    private const KERTAS = [
        'UK100k' => 100000,
        'UK75k' => 75000,
        'UK50k' => 50000,
        'UK20k' => 20000,
        'UK10k' => 10000,
        'UK5k' => 5000,
        'UK2k' => 2000,
        'UK1k' => 1000,
    ];

    private const LOGAM = [
        'UL1k' => 1000,
        'UL500' => 500,
        'UL200' => 200,
        'UL100' => 100,
    ];

    protected function resolveAuthorizedKas(Request $request, int $kasId): Kas
    {
        // Muat rantai ke client untuk menurunkan NamaPerusahaan (DataClient.NamaClient).
        $kas = Kas::with('jwbKasus.kasus.client')->findOrFail($kasId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $kas->JwbKasusID)
            ->firstOrFail();

        return $kas;
    }

    // Nama perusahaan diturunkan dari client (tidak disimpan di cash_count).
    private function namaPerusahaan(Kas $kas): ?string
    {
        return $kas->jwbKasus?->kasus?->client?->NamaClient;
    }

    private function cashCountCheck(Kas $kas): void
    {
        $kas->updateQuietly([
            'CashCountCheck' => $kas->cashCount()->exists(),
        ]);
    }

    protected function serialize(CashCount $cashCount, ?string $namaPerusahaan = null): array
    {
        $cashCount->loadMissing('danaLain');

        $data = [
            'CashCountID' => $cashCount->CashCountID,
            'KasID' => $cashCount->KasID,
            'NamaPerusahaan' => $namaPerusahaan,
            'JenisKas' => $cashCount->JenisKas,
            'TanggalCashCount' => $cashCount->TanggalCashCount?->format('Y-m-d'),
            'SaldoBuku' => $cashCount->SaldoBuku,
            'Penjelasan' => $cashCount->Penjelasan,
            'TotalKertas' => $cashCount->TotalKertas,
            'TotalLogam' => $cashCount->TotalLogam,
            'TotalDanaLain' => $cashCount->TotalDanaLain,
            'TotalKeseluruhan' => $cashCount->TotalKeseluruhan,
            'SelisihLebihKurang' => $cashCount->SelisihLebihKurang,
            'danaLain' => $cashCount->danaLain
                ->sortBy('DanaLainID')
                ->values()
                ->map(fn ($item) => [
                    'DanaLainID' => $item->DanaLainID,
                    'Keterangan' => $item->Keterangan,
                    'Jumlah' => $item->Jumlah,
                ])->all(),
        ];

        foreach (array_keys(self::KERTAS + self::LOGAM) as $column) {
            $data[$column] = $cashCount->{$column};
        }

        return $data;
    }

    /**
     * GET /api/kas/{kasId}/cash-count
     */
    public function show(Request $request, int $kasId): JsonResponse
    {
        $kas = $this->resolveAuthorizedKas($request, $kasId);

        $cashCount = $kas->cashCount()->first();

        return response()->json([
            'success' => true,
            'data' => $cashCount
                ? $this->serialize($cashCount, $this->namaPerusahaan($kas))
                : [
                    'NamaPerusahaan' => $this->namaPerusahaan($kas),
                ],
        ]);
    }

    /**
     * POST /api/kas/{kasId}/cash-count
     *
     * Upsert 1:1 dengan Kas: simpan/update header + sync dana lain.
     * Semua total dihitung backend (subtotal per denominasi tidak disimpan).
     */
    public function store(Request $request, int $kasId): JsonResponse
    {
        $countColumnRules = [];
        foreach (array_keys(self::KERTAS + self::LOGAM) as $column) {
            $countColumnRules[$column] = ['nullable', 'integer', 'min:0'];
        }

        $validated = $request->validate([
            'JenisKas' => ['required', 'in:Kas Kecil,Kas Besar'],
            'TanggalCashCount' => ['nullable', 'date'],
            'SaldoBuku' => ['nullable', 'integer'],
            'Penjelasan' => ['nullable', 'string'],

            'danaLain' => ['sometimes', 'array'],
            'danaLain.*.Keterangan' => ['nullable', 'string', 'max:255'],
            'danaLain.*.Jumlah' => ['required_with:danaLain', 'integer', 'min:0'],

            ...$countColumnRules,
        ]);

        $kas = $this->resolveAuthorizedKas($request, $kasId);

        $danaLainRows = $validated['danaLain'] ?? [];

        // --- Hitung total di backend (subtotal per nominal tidak disimpan) ---
        $totalKertas = 0;
        foreach (self::KERTAS as $column => $nominal) {
            $totalKertas += $nominal * (int) ($validated[$column] ?? 0);
        }

        $totalLogam = 0;
        foreach (self::LOGAM as $column => $nominal) {
            $totalLogam += $nominal * (int) ($validated[$column] ?? 0);
        }

        $totalDanaLain = 0;
        foreach ($danaLainRows as $row) {
            $totalDanaLain += (int) $row['Jumlah'];
        }

        $saldoBuku = (int) ($validated['SaldoBuku'] ?? 0);
        $totalKeseluruhan = $totalKertas + $totalLogam + $totalDanaLain;
        $selisih = $saldoBuku - $totalKeseluruhan;

        $cashCount = DB::transaction(function () use (
            $kas,
            $validated,
            $danaLainRows,
            $totalKertas,
            $totalLogam,
            $totalDanaLain,
            $totalKeseluruhan,
            $selisih,
            $saldoBuku
        ) {
            $attributes = [
                'JenisKas' => $validated['JenisKas'],
                'TanggalCashCount' => $validated['TanggalCashCount'] ?? null,
                'SaldoBuku' => $saldoBuku,
                'Penjelasan' => $validated['Penjelasan'] ?? null,
                'TotalKertas' => $totalKertas,
                'TotalLogam' => $totalLogam,
                'TotalDanaLain' => $totalDanaLain,
                'TotalKeseluruhan' => $totalKeseluruhan,
                'SelisihLebihKurang' => $selisih,
            ];

            foreach (array_keys(self::KERTAS + self::LOGAM) as $column) {
                $attributes[$column] = (int) ($validated[$column] ?? 0);
            }

            $cashCount = CashCount::updateOrCreate(
                ['KasID' => $kas->KasID],
                $attributes
            );

            // Sync dana lain: hapus-ganti (full replace).
            $cashCount->danaLain()->delete();
            if (! empty($danaLainRows)) {
                $cashCount->danaLain()->createMany(array_map(fn ($row) => [
                    'Keterangan' => $row['Keterangan'] ?? null,
                    'Jumlah' => (int) $row['Jumlah'],
                ], $danaLainRows));
            }

            $this->cashCountCheck($kas);

            return $cashCount;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cash count berhasil disimpan.',
            'data' => $this->serialize($cashCount->fresh(), $this->namaPerusahaan($kas)),
        ]);
    }
}
