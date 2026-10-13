<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\JurnalKoreksiAsetLain;
use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiAsetLainController extends Controller
{
    protected function resolveAuthorizedAsetLain(Request $request, int $asetLainId): AsetLain
    {
        $asetLain = AsetLain::findOrFail($asetLainId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetLain->JwbKasusID)
            ->firstOrFail();

        return $asetLain;
    }

    private function jurnalKoreksiCheck(AsetLain $asetLain): void
    {
        $hasJurnalKoreksi = $asetLain->jurnalKoreksi()->exists();

        $asetLain->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiAsetLain $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiAsetLainID')
            ->values();

        return [
            'JurnalKoreksiAsetLainID' => $jurnalKoreksi->JurnalKoreksiAsetLainID,
            'AsetLainID' => $jurnalKoreksi->AsetLainID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiAsetLainID' => $item->PembayaranJurnalKoreksiAsetLainID,
                'JurnalKoreksiAsetLainID' => $item->JurnalKoreksiAsetLainID,
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

    protected function syncPembayaran(JurnalKoreksiAsetLain $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
        ]);

        $asetLain = $this->resolveAuthorizedAsetLain($request, $validated['AsetLainID']);

        $items = $asetLain->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiAsetLainID')
            ->get()
            ->map(fn (JurnalKoreksiAsetLain $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
            ...$this->validationRules(),
        ]);

        $asetLain = $this->resolveAuthorizedAsetLain($request, $validated['AsetLainID']);

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $asetLain->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($asetLain, $validated): JurnalKoreksiAsetLain {
            $item = $asetLain->jurnalKoreksi()->create([
                'AsetLainID' => $asetLain->AsetLainID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($asetLain);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset lain-lain berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiAsetLain $jurnalKoreksiAsetLain
    ): JsonResponse {
        $asetLain = $this->resolveAuthorizedAsetLain(
            $request,
            $jurnalKoreksiAsetLain->AsetLainID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $asetLain->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiAsetLain, $asetLain, $validated): void {
            $jurnalKoreksiAsetLain->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiAsetLain,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($asetLain);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset lain-lain berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiAsetLain->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiAsetLain $jurnalKoreksiAsetLain
    ): JsonResponse {
        $asetLain = $this->resolveAuthorizedAsetLain(
            $request,
            $jurnalKoreksiAsetLain->AsetLainID
        );

        DB::transaction(function () use ($jurnalKoreksiAsetLain, $asetLain): void {
            $jurnalKoreksiAsetLain->delete();
            $this->jurnalKoreksiCheck($asetLain);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi aset lain-lain berhasil dihapus.',
        ]);
    }
}
