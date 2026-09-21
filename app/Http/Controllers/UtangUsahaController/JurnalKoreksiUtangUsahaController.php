<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\JurnalKoreksiUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiUtangUsahaController extends Controller
{
    protected function resolveAuthorizedUtangUsaha(Request $request, int $utangUsahaId): UtangUsaha
    {
        $utangUsaha = UtangUsaha::findOrFail($utangUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $utangUsaha->JwbKasusID)
            ->firstOrFail();

        return $utangUsaha;
    }

    private function jurnalKoreksiCheck(UtangUsaha $utangUsaha): void
    {
        $hasJurnalKoreksi = $utangUsaha->jurnalKoreksi()->exists();

        $utangUsaha->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiUtangUsaha $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');
        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiUtangUsahaID')
            ->values();

        return [
            'JurnalKoreksiUtangUsahaID' => $jurnalKoreksi->JurnalKoreksiUtangUsahaID,
            'UtangUsahaID' => $jurnalKoreksi->UtangUsahaID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiUtangUsahaID' => $item->PembayaranJurnalKoreksiUtangUsahaID,
                'JurnalKoreksiUtangUsahaID' => $item->JurnalKoreksiUtangUsahaID,
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

    protected function syncPembayaran(JurnalKoreksiUtangUsaha $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UtangUsahaID' => ['required', 'integer', 'exists:utang_usaha,UtangUsahaID'],
        ]);
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $validated['UtangUsahaID']);
        $items = $utangUsaha->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiUtangUsahaID')
            ->get()
            ->map(fn (JurnalKoreksiUtangUsaha $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UtangUsahaID' => ['required', 'integer', 'exists:utang_usaha,UtangUsahaID'],
            ...$this->validationRules(),
        ]);
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $validated['UtangUsahaID']);
        $this->validatePaymentsForCase($validated['pembayaran'], $utangUsaha->JwbKasusID);

        $jurnalKoreksi = DB::transaction(function () use ($utangUsaha, $validated): JurnalKoreksiUtangUsaha {
            $item = $utangUsaha->jurnalKoreksi()->create([
                'UtangUsahaID' => $utangUsaha->UtangUsahaID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);
            $this->jurnalKoreksiCheck($utangUsaha);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi utang usaha berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(Request $request, JurnalKoreksiUtangUsaha $jurnalKoreksiUtangUsaha): JsonResponse
    {
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $jurnalKoreksiUtangUsaha->UtangUsahaID);
        $validated = $request->validate($this->validationRules());
        $this->validatePaymentsForCase($validated['pembayaran'], $utangUsaha->JwbKasusID);

        DB::transaction(function () use ($jurnalKoreksiUtangUsaha, $utangUsaha, $validated): void {
            $jurnalKoreksiUtangUsaha->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);
            $this->syncPembayaran($jurnalKoreksiUtangUsaha, $validated['pembayaran']);
            $this->jurnalKoreksiCheck($utangUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi utang usaha berhasil diperbarui.',
            'data' => $this->serializeItem($jurnalKoreksiUtangUsaha->fresh()),
        ]);
    }

    public function destroy(Request $request, JurnalKoreksiUtangUsaha $jurnalKoreksiUtangUsaha): JsonResponse
    {
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $jurnalKoreksiUtangUsaha->UtangUsahaID);

        DB::transaction(function () use ($jurnalKoreksiUtangUsaha, $utangUsaha): void {
            $jurnalKoreksiUtangUsaha->delete();
            $this->jurnalKoreksiCheck($utangUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi utang usaha berhasil dihapus.',
        ]);
    }
}
