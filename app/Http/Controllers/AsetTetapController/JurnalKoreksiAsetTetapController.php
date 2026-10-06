<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\AsetTetap\AsetTetap;
use App\Models\AsetTetap\JurnalKoreksiAsetTetap;
use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiAsetTetapController extends Controller
{
    protected function resolveAuthorizedAsetTetap(Request $request, int $asetTetapId): AsetTetap
    {
        $asetTetap = AsetTetap::findOrFail($asetTetapId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetTetap->JwbKasusID)
            ->firstOrFail();

        return $asetTetap;
    }

    private function jurnalKoreksiCheck(AsetTetap $asetTetap): void
    {
        $hasJurnalKoreksi = $asetTetap->jurnalKoreksi()->exists();

        $asetTetap->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiAsetTetap $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiAsetTetapID')
            ->values();

        return [
            'JurnalKoreksiAsetTetapID' => $jurnalKoreksi->JurnalKoreksiAsetTetapID,
            'AsetTetapID' => $jurnalKoreksi->AsetTetapID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiAsetTetapID' => $item->PembayaranJurnalKoreksiAsetTetapID,
                'JurnalKoreksiAsetTetapID' => $item->JurnalKoreksiAsetTetapID,
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

    protected function syncPembayaran(JurnalKoreksiAsetTetap $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetTetapID' => ['required', 'integer', 'exists:aset_tetap,AsetTetapID'],
        ]);

        $asetTetap = $this->resolveAuthorizedAsetTetap($request, $validated['AsetTetapID']);

        $items = $asetTetap->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiAsetTetapID')
            ->get()
            ->map(fn (JurnalKoreksiAsetTetap $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetTetapID' => ['required', 'integer', 'exists:aset_tetap,AsetTetapID'],
            ...$this->validationRules(),
        ]);

        $asetTetap = $this->resolveAuthorizedAsetTetap($request, $validated['AsetTetapID']);

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $asetTetap->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($asetTetap, $validated): JurnalKoreksiAsetTetap {
            $item = $asetTetap->jurnalKoreksi()->create([
                'AsetTetapID' => $asetTetap->AsetTetapID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($asetTetap);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset tetap berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiAsetTetap $jurnalKoreksiAsetTetap
    ): JsonResponse {
        $asetTetap = $this->resolveAuthorizedAsetTetap(
            $request,
            $jurnalKoreksiAsetTetap->AsetTetapID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $asetTetap->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiAsetTetap, $asetTetap, $validated): void {
            $jurnalKoreksiAsetTetap->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiAsetTetap,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($asetTetap);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset tetap berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiAsetTetap->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiAsetTetap $jurnalKoreksiAsetTetap
    ): JsonResponse {
        $asetTetap = $this->resolveAuthorizedAsetTetap(
            $request,
            $jurnalKoreksiAsetTetap->AsetTetapID
        );

        DB::transaction(function () use ($jurnalKoreksiAsetTetap, $asetTetap): void {
            $jurnalKoreksiAsetTetap->delete();
            $this->jurnalKoreksiCheck($asetTetap);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset tetap berhasil dihapus.',
        ]);
    }
}
