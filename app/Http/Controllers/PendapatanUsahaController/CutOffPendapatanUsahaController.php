<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\PendapatanUsaha\CutOffPendapatanUsaha;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CutOffPendapatanUsahaController extends Controller
{
    protected function resolveAuthorizedPendapatanUsaha(Request $request, int $pendapatanUsahaId): PendapatanUsaha
    {
        $pendapatanUsaha = PendapatanUsaha::findOrFail($pendapatanUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $pendapatanUsaha->JwbKasusID)
            ->firstOrFail();

        return $pendapatanUsaha;
    }

    private function cutOffCheck(int $pendapatanUsahaId): void
    {
        $pendapatanUsaha = PendapatanUsaha::find($pendapatanUsahaId);

        if (! $pendapatanUsaha) {
            return;
        }

        $pendapatanUsaha->updateQuietly([
            'CutOffCheck' => $pendapatanUsaha->cutOff()->exists(),
        ]);
    }

    protected function serializeItem(CutOffPendapatanUsaha $item): array
    {
        return [
            'CutOffID' => $item->CutOffID,
            'PendapatanUsahaID' => $item->PendapatanUsahaID,
            'Periode' => $item->Periode,
            'NamaPelanggan' => $item->NamaPelanggan,
            'NomorFaktur' => $item->NomorFaktur,
            'TanggalFaktur' => $item->TanggalFaktur?->format('Y-m-d'),
            'Jumlah' => $item->Jumlah,
            'TanggalDelivery' => $item->TanggalDelivery?->format('Y-m-d'),
            'SesuaiPeriode' => $item->SesuaiPeriode,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    protected function rowRules(string $prefix = ''): array
    {
        return [
            "{$prefix}Periode" => ['required', 'in:sebelum,sesudah'],
            "{$prefix}NamaPelanggan" => ['nullable', 'string', 'max:255'],
            "{$prefix}NomorFaktur" => ['nullable', 'string', 'max:255'],
            "{$prefix}TanggalFaktur" => ['nullable', 'date'],
            "{$prefix}Jumlah" => ['nullable', 'integer'],
            "{$prefix}TanggalDelivery" => ['nullable', 'date'],
            "{$prefix}SesuaiPeriode" => ['required', 'boolean'],
        ];
    }

    protected function rowPayload(array $row): array
    {
        return [
            'Periode' => $row['Periode'],
            'NamaPelanggan' => $row['NamaPelanggan'] ?? null,
            'NomorFaktur' => $row['NomorFaktur'] ?? null,
            'TanggalFaktur' => $row['TanggalFaktur'] ?? null,
            'Jumlah' => (int) ($row['Jumlah'] ?? 0),
            'TanggalDelivery' => $row['TanggalDelivery'] ?? null,
            'SesuaiPeriode' => (bool) $row['SesuaiPeriode'],
        ];
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

        $items = $pendapatanUsaha->cutOff()
            ->orderBy('CutOffID')
            ->get()
            ->map(fn (CutOffPendapatanUsaha $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * POST /api/cut-off-pendapatan-usaha
     *
     * Bulk save: kirim seluruh baris sekaligus.
     * - id null   -> CREATE
     * - id exists -> UPDATE
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PendapatanUsahaID' => ['required', 'integer', 'exists:pendapatan_usaha,PendapatanUsahaID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['nullable', 'integer', 'exists:cut_off_pendapatan_usaha,CutOffID'],
            ...$this->rowRules('rows.*.'),
        ]);

        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $validated['PendapatanUsahaID']
        );

        $ids = collect($validated['rows'])
            ->pluck('id')
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existing = CutOffPendapatanUsaha::whereIn('CutOffID', $ids)
            ->get()
            ->keyBy('CutOffID');

        foreach ($ids as $id) {
            $row = $existing->get($id);

            if (! $row) {
                return response()->json([
                    'message' => "Cut off ID {$id} tidak ditemukan.",
                ], 422);
            }

            if ((int) $row->PendapatanUsahaID !== (int) $pendapatanUsaha->PendapatanUsahaID) {
                return response()->json([
                    'message' => "Cut off ID {$id} bukan milik Pendapatan Usaha ini.",
                ], 422);
            }
        }

        $saved = DB::transaction(function () use ($validated, $pendapatanUsaha, $existing) {
            $result = [];

            foreach ($validated['rows'] as $row) {
                $payload = $this->rowPayload($row);

                if (! is_null($row['id'] ?? null)) {
                    $item = $existing->get((int) $row['id']);
                    $item->update($payload);
                } else {
                    $item = $pendapatanUsaha->cutOff()->create(array_merge(
                        ['PendapatanUsahaID' => $pendapatanUsaha->PendapatanUsahaID],
                        $payload
                    ));
                }

                $result[] = $item->fresh();
            }

            $this->cutOffCheck((int) $pendapatanUsaha->PendapatanUsahaID);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cut off pendapatan usaha berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (CutOffPendapatanUsaha $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function update(
        Request $request,
        CutOffPendapatanUsaha $cutOffPendapatanUsaha
    ): JsonResponse {
        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $cutOffPendapatanUsaha->PendapatanUsahaID
        );

        $validated = $request->validate($this->rowRules());

        $cutOffPendapatanUsaha->update($this->rowPayload($validated));

        $this->cutOffCheck((int) $pendapatanUsaha->PendapatanUsahaID);

        return response()->json([
            'success' => true,
            'message' => 'Data cut off pendapatan usaha berhasil diperbarui.',
            'data' => $this->serializeItem($cutOffPendapatanUsaha->fresh()),
        ]);
    }

    public function destroy(
        Request $request,
        CutOffPendapatanUsaha $cutOffPendapatanUsaha
    ): JsonResponse {
        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            $cutOffPendapatanUsaha->PendapatanUsahaID
        );

        DB::transaction(function () use ($cutOffPendapatanUsaha, $pendapatanUsaha): void {
            $cutOffPendapatanUsaha->delete();
            $this->cutOffCheck((int) $pendapatanUsaha->PendapatanUsahaID);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cut off pendapatan usaha berhasil dihapus.',
        ]);
    }
}
