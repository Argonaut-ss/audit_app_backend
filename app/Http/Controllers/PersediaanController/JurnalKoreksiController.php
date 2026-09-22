<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\Persediaan\JurnalKoreksiPersediaan;
use App\Models\Persediaan\Persediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiPersediaanController extends Controller
{
    protected function resolveAuthorizedPersediaan(Request $request, int $persediaanId): Persediaan
    {
        $persediaan = Persediaan::findOrFail($persediaanId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        return $persediaan;
    }

    private function jurnalKoreksiCheck(Persediaan $persediaan): void
    {
        $hasJurnalKoreksi = $persediaan->jurnalKoreksi()->exists();

        $persediaan->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiPersediaan $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiPersediaanID')
            ->values();

        return [
            'JurnalKoreksiPersediaanID' => $jurnalKoreksi->JurnalKoreksiPersediaanID,
            'PersediaanID' => $jurnalKoreksi->PersediaanID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiPersediaanID' => $item->PembayaranJurnalKoreksiPersediaanID,
                'JurnalKoreksiPersediaanID' => $item->JurnalKoreksiPersediaanID,
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

    protected function syncPembayaran(JurnalKoreksiPersediaan $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
        ]);

        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $validated['PersediaanID']
        );

        $items = $persediaan->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiPersediaanID')
            ->get()
            ->map(fn (JurnalKoreksiPersediaan $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
            ...$this->validationRules(),
        ]);

        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $validated['PersediaanID']
        );

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $persediaan->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($persediaan, $validated): JurnalKoreksiPersediaan {
            $item = $persediaan->jurnalKoreksi()->create([
                'PersediaanID' => $persediaan->PersediaanID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($persediaan);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi persediaan berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiPersediaan $jurnalKoreksiPersediaan
    ): JsonResponse {
        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $jurnalKoreksiPersediaan->PersediaanID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $persediaan->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiPersediaan, $persediaan, $validated): void {
            $jurnalKoreksiPersediaan->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiPersediaan,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($persediaan);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi persediaan berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiPersediaan->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiPersediaan $jurnalKoreksiPersediaan
    ): JsonResponse {
        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $jurnalKoreksiPersediaan->PersediaanID
        );

        DB::transaction(function () use ($jurnalKoreksiPersediaan, $persediaan): void {
            $jurnalKoreksiPersediaan->delete();
            $this->jurnalKoreksiCheck($persediaan);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi persediaan berhasil dihapus.',
        ]);
    }
}