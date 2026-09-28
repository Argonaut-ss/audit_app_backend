<?php

namespace App\Http\Controllers\BebanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\BebanUsaha\JurnalKoreksiBebanUsaha;
use App\Models\BebanUsaha\BebanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiBebanUsahaController extends Controller
{
    protected function resolveAuthorizedBebanUsaha(Request $request, int $bebanUsahaId): BebanUsaha
    {
        $bebanUsaha = BebanUsaha::findOrFail($bebanUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $bebanUsaha->JwbKasusID)
            ->firstOrFail();

        return $bebanUsaha;
    }

    private function jurnalKoreksiCheck(BebanUsaha $bebanUsaha): void
    {
        $hasJurnalKoreksi = $bebanUsaha->jurnalKoreksi()->exists();

        $bebanUsaha->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiBebanUsaha $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiBebanUsahaID')
            ->values();

        return [
            'JurnalKoreksiBebanUsahaID' => $jurnalKoreksi->JurnalKoreksiBebanUsahaID,
            'BebanUsahaID' => $jurnalKoreksi->BebanUsahaID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiBebanUsahaID' => $item->PembayaranJurnalKoreksiBebanUsahaID,
                'JurnalKoreksiBebanUsahaID' => $item->JurnalKoreksiBebanUsahaID,
                'COAID' => $item->COAID,
                'Debet' => $item->Debet,
                'Kredit' => $item->Kredit,
                'coa' => $item->coa ? [
                    'COAID' => $item->coa->COAID,
                    'NoAkun' => $item->coa->NoAkun,
                    'NamaAkun' => $item->coa->NamaAkun,
                ] : null,
            ])->all(),
            'TotalDebet' => $pembayaran->sum('Debet'),
            'TotalKredit' => $pembayaran->sum('Kredit'),
            'created_at' => $jurnalKoreksi->created_at,
            'updated_at' => $jurnalKoreksi->updated_at,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'Keterangan' => ['nullable', 'string', 'max:5000'],
            'pembayaran' => ['required', 'array', 'min:1'],
            'pembayaran.*.COAID' => ['required', 'integer', 'exists:coa,COAID'],
            'pembayaran.*.Debet' => ['required', 'integer', 'min:0'],
            'pembayaran.*.Kredit' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function validatePaymentsForCase(array $pembayaran, int $jwbKasusId): void
    {
        foreach ($pembayaran as $item) {
            COA::query()
                ->where('COAID', $item['COAID'])
                ->where('JwbKasusID', $jwbKasusId)
                ->firstOrFail();

            $debet = (int) $item['Debet'];
            $kredit = (int) $item['Kredit'];

            if (($debet === 0 && $kredit === 0) || ($debet > 0 && $kredit > 0)) {
                abort(422, 'Setiap pembayaran harus memiliki salah satu nilai Debet atau Kredit yang lebih dari nol.');
            }
        }
    }

    protected function syncPembayaran(JurnalKoreksiBebanUsaha $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'BebanUsahaID' => ['required', 'integer', 'exists:beban_usaha,BebanUsahaID'],
        ]);

        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $validated['BebanUsahaID']
        );

        $items = $bebanUsaha->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiBebanUsahaID')
            ->get()
            ->map(fn (JurnalKoreksiBebanUsaha $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'BebanUsahaID' => ['required', 'integer', 'exists:beban_usaha,BebanUsahaID'],
            ...$this->validationRules(),
        ]);

        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $validated['BebanUsahaID']
        );

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $bebanUsaha->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($bebanUsaha, $validated): JurnalKoreksiBebanUsaha {
            $item = $bebanUsaha->jurnalKoreksi()->create([
                'BebanUsahaID' => $bebanUsaha->BebanUsahaID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($bebanUsaha);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi beban usaha berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiBebanUsaha $jurnalKoreksiBebanUsaha
    ): JsonResponse {
        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $jurnalKoreksiBebanUsaha->BebanUsahaID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $bebanUsaha->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiBebanUsaha, $bebanUsaha, $validated): void {
            $jurnalKoreksiBebanUsaha->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiBebanUsaha,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($bebanUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi beban usaha berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiBebanUsaha->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiBebanUsaha $jurnalKoreksiBebanUsaha
    ): JsonResponse {
        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $jurnalKoreksiBebanUsaha->BebanUsahaID
        );

        DB::transaction(function () use ($jurnalKoreksiBebanUsaha, $bebanUsaha): void {
            $jurnalKoreksiBebanUsaha->delete();
            $this->jurnalKoreksiCheck($bebanUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi beban usaha berhasil dihapus.',
        ]);
    }
}
