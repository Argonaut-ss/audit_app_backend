<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Persediaan\Persediaan;
use App\Models\Persediaan\StokOpnamePersediaan;
use App\Models\Persediaan\UjiMutasiPersediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UjiMutasiPersediaanController extends Controller
{
    private function resolveAuthorizedStokOpname(
        Request $request,
        int $stokOpnameId
    ): StokOpnamePersediaan {
        $stokOpname = StokOpnamePersediaan::with('persediaan')
            ->findOrFail($stokOpnameId);

        $persediaan = $stokOpname->persediaan;

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        return $stokOpname;
    }

    protected function calculate(
        int $saldoStokOpname,
        int $keluar,
        int $rusak,
        int $masuk,
        int $saldoNeraca
    ): array {
        $saldoAuditSblm = $saldoStokOpname - $keluar - $rusak + $masuk;
        $saldoAkhirSblm = $saldoAuditSblm - $saldoNeraca;
        $saldoAkhirSdh = $saldoStokOpname + $keluar + $rusak
            - $masuk - $saldoNeraca;
        $saldoAuditSdh = $saldoNeraca + $saldoAkhirSdh;

        return [
            'SaldoStokOpname' => $saldoStokOpname,
            'Keluar' => $keluar,
            'Rusak' => $rusak,
            'Masuk' => $masuk,
            'SaldoAuditSblm' => $saldoAuditSblm,
            'SaldoAkhirSblm' => $saldoAkhirSblm,
            'SaldoAuditSdh' => $saldoAuditSdh,
            'SaldoAkhirSdh' => $saldoAkhirSdh,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'SaldoStokOpname' => ['nullable', 'integer'],
            'Keluar' => ['nullable', 'integer'],
            'Rusak' => ['nullable', 'integer'],
            'Masuk' => ['nullable', 'integer'],
        ];
    }

    protected function payloadFrom(
        array $validated,
        ?UjiMutasiPersediaan $ujiMutasi = null
    ): array {

        return [
            'SaldoStokOpname' => (int) ($validated['SaldoStokOpname']
                ?? $ujiMutasi?->SaldoStokOpname ?? 0),
            'Keluar' => (int) ($validated['Keluar']
                ?? $ujiMutasi?->Keluar ?? 0),
            'Rusak' => (int) ($validated['Rusak']
                ?? $ujiMutasi?->Rusak ?? 0),
            'Masuk' => (int) ($validated['Masuk']
                ?? $ujiMutasi?->Masuk ?? 0),
        ];
    }

    protected function serializeItem(UjiMutasiPersediaan $ujiMutasi): array
    {
        $stokOpname = $ujiMutasi->stokOpname;

        return [
            'UjiMutasiID' => $ujiMutasi->UjiMutasiID,
            'PersediaanID' => $ujiMutasi->PersediaanID,
            'StokOpnameID' => $ujiMutasi->StokOpnameID,
            'NamaPersediaan' => $stokOpname?->NamaPersediaan,
            'Satuan' => $stokOpname?->Satuan,
            'SaldoNeraca' => $stokOpname?->SaldoNeraca,
            'SaldoStokOpname' => $ujiMutasi->SaldoStokOpname,
            'Keluar' => $ujiMutasi->Keluar,
            'Rusak' => $ujiMutasi->Rusak,
            'Masuk' => $ujiMutasi->Masuk,
            'SaldoAuditSblm' => $ujiMutasi->SaldoAuditSblm,
            'SaldoAkhirSblm' => $ujiMutasi->SaldoAkhirSblm,
            'SaldoAuditSdh' => $ujiMutasi->SaldoAuditSdh,
            'SaldoAkhirSdh' => $ujiMutasi->SaldoAkhirSdh,
            'created_at' => $ujiMutasi->created_at,
            'updated_at' => $ujiMutasi->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
        ]);

        $persediaan = Persediaan::findOrFail($validated['PersediaanID']);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        $items = UjiMutasiPersediaan::with('stokOpname')
            ->where('PersediaanID', $persediaan->PersediaanID)
            ->orderBy('UjiMutasiID')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(
                fn (UjiMutasiPersediaan $item) => $this->serializeItem($item)
            )->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
            'StokOpnameID' => ['required', 'integer', 'exists:stok_opname_persediaan,StokOpnameID'],
        ]);

        $stokOpname = $this->resolveAuthorizedStokOpname(
            $request,
            (int) $validated['StokOpnameID']
        );

        if ((int) $stokOpname->PersediaanID !== (int) $validated['PersediaanID']) {
            return response()->json([
                'message' => 'Stok opname bukan milik Persediaan ini.',
            ], 422);
        }

        if (UjiMutasiPersediaan::where(
            'StokOpnameID',
            $stokOpname->StokOpnameID
        )->exists()) {
            return response()->json([
                'message' => 'Uji mutasi untuk stok opname ini sudah tersedia.',
            ], 409);
        }

        $ujiMutasi = DB::transaction(function () use ($request, $stokOpname) {
            $validated = $request->validate($this->validationRules());
            $payload = $this->payloadFrom($validated);
            $calculated = $this->calculate(
                $payload['SaldoStokOpname'],
                $payload['Keluar'],
                $payload['Rusak'],
                $payload['Masuk'],
                (int) $stokOpname->SaldoNeraca
            );

            return UjiMutasiPersediaan::create(array_merge([
                    'PersediaanID' => $stokOpname->PersediaanID,
                    'StokOpnameID' => $stokOpname->StokOpnameID,
                ],
                $calculated
            ));
        });

        return response()->json([
            'success' => true,
            'message' => 'Uji mutasi berhasil dibuat.',
            'data' => $this->serializeItem($ujiMutasi->load('stokOpname')),
        ], 201);
    }

    public function show(
        Request $request,
        UjiMutasiPersediaan $uji_mutasi_persediaan
    ): JsonResponse {
        $this->resolveAuthorizedStokOpname(
            $request,
            (int) $uji_mutasi_persediaan->StokOpnameID
        );

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($uji_mutasi_persediaan->load('stokOpname')),
        ]);
    }

    public function update(
        Request $request,
        ?UjiMutasiPersediaan $uji_mutasi_persediaan = null
    ): JsonResponse {
        if ($request->has('rows')) {
            return $this->bulkUpdate($request);
        }

        if (! $uji_mutasi_persediaan) {
            abort(404);
        }

        $stokOpname = $this->resolveAuthorizedStokOpname(
            $request,
            (int) $uji_mutasi_persediaan->StokOpnameID
        );
        $validated = $request->validate($this->validationRules());
        $payload = $this->payloadFrom($validated, $uji_mutasi_persediaan);

        $uji_mutasi_persediaan->update($this->calculate(
            $payload['SaldoStokOpname'],
            $payload['Keluar'],
            $payload['Rusak'],
            $payload['Masuk'],
            (int) $stokOpname->SaldoNeraca
        ));

        return response()->json([
            'success' => true,
            'message' => 'Uji mutasi berhasil diperbarui.',
            'data' => $this->serializeItem(
                $uji_mutasi_persediaan->fresh()->load('stokOpname')
            ),
        ]);
    }

    protected function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => [
                'required',
                'integer',
                'exists:uji_mutasi_persediaan,UjiMutasiID',
            ],
            'rows.*.SaldoStokOpname' => ['nullable', 'integer'],
            'rows.*.Keluar' => ['nullable', 'integer'],
            'rows.*.Rusak' => ['nullable', 'integer'],
            'rows.*.Masuk' => ['nullable', 'integer'],
        ]);

        $persediaan = Persediaan::findOrFail($validated['PersediaanID']);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        $ids = collect($validated['rows'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existing = UjiMutasiPersediaan::with('stokOpname')
            ->whereIn('UjiMutasiID', $ids)
            ->get()
            ->keyBy('UjiMutasiID');

        foreach ($ids as $id) {
            $item = $existing->get($id);

            if (! $item || (int) $item->PersediaanID
                !== (int) $persediaan->PersediaanID) {
                return response()->json([
                    'message' => "Uji mutasi ID {$id} bukan milik Persediaan ini.",
                ], 422);
            }
        }

        $saved = DB::transaction(function () use ($validated, $existing) {
            $result = [];
            foreach ($validated['rows'] as $row) {
                $item = $existing->get((int) $row['id']);
                $payload = $this->payloadFrom($row, $item);

                $item->update($this->calculate(
                    $payload['SaldoStokOpname'],
                    $payload['Keluar'],
                    $payload['Rusak'],
                    $payload['Masuk'],
                    (int) $item->stokOpname->SaldoNeraca
                ));
                $result[] = $item->fresh()->load('stokOpname');
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data uji mutasi berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (UjiMutasiPersediaan $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function destroy(Request $request, UjiMutasiPersediaan $uji_mutasi_persediaan): JsonResponse {
        $this->resolveAuthorizedStokOpname($request, (int) $uji_mutasi_persediaan->StokOpnameID);
        $uji_mutasi_persediaan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Uji mutasi berhasil dihapus.',
        ]);
    }
}
