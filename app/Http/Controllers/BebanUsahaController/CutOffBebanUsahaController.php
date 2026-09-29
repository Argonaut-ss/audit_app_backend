<?php

namespace App\Http\Controllers\BebanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\BebanUsaha\CutOffBebanUsaha;
use App\Models\BebanUsaha\BebanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CutOffBebanUsahaController extends Controller
{
    protected function resolveAuthorizedBebanUsaha(Request $request, int $bebanUsahaId): BebanUsaha
    {
        $bebanUsaha = BebanUsaha::findOrFail($bebanUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $bebanUsaha->JwbKasusID)
            ->firstOrFail();

        return $bebanUsaha;
    }

    private function cutOffCheck(int $bebanUsahaId): void
    {
        $bebanUsaha = BebanUsaha::find($bebanUsahaId);

        if (! $bebanUsaha) {
            return;
        }

        $bebanUsaha->updateQuietly([
            'CutOffCheck' => $bebanUsaha->cutOff()->exists(),
        ]);
    }

    protected function serializeItem(CutOffBebanUsaha $item): array
    {
        return [
            'CutOffID' => $item->CutOffID,
            'BebanUsahaID' => $item->BebanUsahaID,
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
            "{$prefix}NamaPelanggan" => ['required', 'string', 'max:255'],
            "{$prefix}NomorFaktur" => ['required', 'string', 'max:255'],
            "{$prefix}TanggalFaktur" => ['required', 'date'],
            "{$prefix}Jumlah" => ['required', 'integer', 'min:1'],
            "{$prefix}TanggalDelivery" => ['required', 'date'],
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
            'BebanUsahaID' => ['required', 'integer', 'exists:beban_usaha,BebanUsahaID'],
        ]);

        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $validated['BebanUsahaID']
        );

        $items = $bebanUsaha->cutOff()
            ->orderBy('CutOffID')
            ->get()
            ->map(fn (CutOffBebanUsaha $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * POST /api/cut-off-beban-usaha
     *
     * Bulk save: kirim seluruh baris sekaligus.
     * - id null   -> CREATE
     * - id exists -> UPDATE
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'BebanUsahaID' => ['required', 'integer', 'exists:beban_usaha,BebanUsahaID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['nullable', 'integer', 'exists:cut_off_beban_usaha,CutOffID'],
            ...$this->rowRules('rows.*.'),
        ]);

        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $validated['BebanUsahaID']
        );

        $ids = collect($validated['rows'])
            ->pluck('id')
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existing = CutOffBebanUsaha::whereIn('CutOffID', $ids)
            ->get()
            ->keyBy('CutOffID');

        foreach ($ids as $id) {
            $row = $existing->get($id);

            if (! $row) {
                return response()->json([
                    'message' => "Cut off ID {$id} tidak ditemukan.",
                ], 422);
            }

            if ((int) $row->BebanUsahaID !== (int) $bebanUsaha->BebanUsahaID) {
                return response()->json([
                    'message' => "Cut off ID {$id} bukan milik Beban Usaha ini.",
                ], 422);
            }
        }

        $saved = DB::transaction(function () use ($validated, $bebanUsaha, $existing) {
            $result = [];

            foreach ($validated['rows'] as $row) {
                $payload = $this->rowPayload($row);

                if (! is_null($row['id'] ?? null)) {
                    $item = $existing->get((int) $row['id']);
                    $item->update($payload);
                } else {
                    $item = $bebanUsaha->cutOff()->create(array_merge(
                        ['BebanUsahaID' => $bebanUsaha->BebanUsahaID],
                        $payload
                    ));
                }

                $result[] = $item->fresh();
            }

            $this->cutOffCheck((int) $bebanUsaha->BebanUsahaID);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cut off beban usaha berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (CutOffBebanUsaha $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function update(
        Request $request,
        CutOffBebanUsaha $cutOffBebanUsaha
    ): JsonResponse {
        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $cutOffBebanUsaha->BebanUsahaID
        );

        $validated = $request->validate($this->rowRules());

        $cutOffBebanUsaha->update($this->rowPayload($validated));

        $this->cutOffCheck((int) $bebanUsaha->BebanUsahaID);

        return response()->json([
            'success' => true,
            'message' => 'Data cut off beban usaha berhasil diperbarui.',
            'data' => $this->serializeItem($cutOffBebanUsaha->fresh()),
        ]);
    }

    public function destroy(
        Request $request,
        CutOffBebanUsaha $cutOffBebanUsaha
    ): JsonResponse {
        $bebanUsaha = $this->resolveAuthorizedBebanUsaha(
            $request,
            $cutOffBebanUsaha->BebanUsahaID
        );

        DB::transaction(function () use ($cutOffBebanUsaha, $bebanUsaha): void {
            $cutOffBebanUsaha->delete();
            $this->cutOffCheck((int) $bebanUsaha->BebanUsahaID);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cut off beban usaha berhasil dihapus.',
        ]);
    }
}
