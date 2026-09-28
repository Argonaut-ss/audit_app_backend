<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\PendapatanUsaha\JurnalKoreksiPendapatanUsaha;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiPendapatanUsahaController extends Controller
{
    protected function resolveAuthorizedPendapatanUsaha(Request $request, int $pendapatanUsahaId): PendapatanUsaha
    {
        $pendapatanUsaha = PendapatanUsaha::findOrFail($pendapatanUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $pendapatanUsaha->JwbKasusID)
            ->firstOrFail();

        return $pendapatanUsaha;
    }

    private function jurnalKoreksiCheck(PendapatanUsaha $pendapatanUsaha): void
    {
        $hasJurnalKoreksi = $pendapatanUsaha->jurnalKoreksi()->exists();

        $pendapatanUsaha->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiPendapatanUsaha $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiPendapatanUsahaID')
            ->values();

        return [
            'JurnalKoreksiPendapatanUsahaID' => $jurnalKoreksi->JurnalKoreksiPendapatanUsahaID,
            'PendapatanUsahaID' => $jurnalKoreksi->PendapatanUsahaID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiPendapatanUsahaID' => $item->PembayaranJurnalKoreksiPendapatanUsahaID,
                'JurnalKoreksiPendapatanUsahaID' => $item->JurnalKoreksiPendapatanUsahaID,
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

    protected function syncPembayaran(JurnalKoreksiPendapatanUsaha $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PendapatanUsahaID' => ['required', 'integer', 'exists:pendapatan_usaha,PendapatanUsahaID'],
        ]);

        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $validated['PendapatanUsahaID']
        );

        $items = $pendapatanUsaha->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiPendapatanUsahaID')
            ->get()
            ->map(fn (JurnalKoreksiPendapatanUsaha $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PendapatanUsahaID' => ['required', 'integer', 'exists:pendapatan_usaha,PendapatanUsahaID'],
            ...$this->validationRules(),
        ]);

        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $validated['PendapatanUsahaID']
        );

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $pendapatanUsaha->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($pendapatanUsaha, $validated): JurnalKoreksiPendapatanUsaha {
            $item = $pendapatanUsaha->jurnalKoreksi()->create([
                'PendapatanUsahaID' => $pendapatanUsaha->PendapatanUsahaID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($pendapatanUsaha);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi pendapatan usaha berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiPendapatanUsaha $jurnalKoreksiPendapatanUsaha
    ): JsonResponse {
        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $jurnalKoreksiPendapatanUsaha->PendapatanUsahaID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $pendapatanUsaha->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiPendapatanUsaha, $pendapatanUsaha, $validated): void {
            $jurnalKoreksiPendapatanUsaha->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiPendapatanUsaha,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($pendapatanUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi pendapatan usaha berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiPendapatanUsaha->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiPendapatanUsaha $jurnalKoreksiPendapatanUsaha
    ): JsonResponse {
        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $jurnalKoreksiPendapatanUsaha->PendapatanUsahaID
        );

        DB::transaction(function () use ($jurnalKoreksiPendapatanUsaha, $pendapatanUsaha): void {
            $jurnalKoreksiPendapatanUsaha->delete();
            $this->jurnalKoreksiCheck($pendapatanUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi pendapatan usaha berhasil dihapus.',
        ]);
    }
}
