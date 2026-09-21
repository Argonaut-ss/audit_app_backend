<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\RekonsiliasiUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekonsiliasiUtangUsahaController extends Controller
{
    protected function resolveAuthorizedUtangUsaha(Request $request, int $utangUsahaId): UtangUsaha
    {
        $utangUsaha = UtangUsaha::findOrFail($utangUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $utangUsaha->JwbKasusID)
            ->firstOrFail();

        return $utangUsaha;
    }

    private function rekonsiliasiCheck(UtangUsaha $utangUsaha): void
    {
        $hasRekonsiliasi = $utangUsaha->rekonsiliasiUtangUsaha()->exists();

        $utangUsaha->updateQuietly([
            'RekonsiliasiCheck' => $hasRekonsiliasi,
        ]);
    }

    protected function serializeItem(RekonsiliasiUtangUsaha $item): array
    {
        return [
            'RekonsiliasiUtangUsahaID' => $item->RekonsiliasiUtangUsahaID,
            'UtangUsahaID' => $item->UtangUsahaID,
            'KonfirmasiUtangUsahaID' => $item->KonfirmasiUtangUsahaID,
            'NomorFaktur' => $item->NomorFaktur,
            'TanggalFaktur' => $item->TanggalFaktur?->format('Y-m-d'),
            'SaldoBuku' => $item->SaldoBuku,
            'SaldoCustomer' => $item->SaldoCustomer,
            'Selisih' => $item->Selisih,
            'Keterangan' => $item->Keterangan,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    protected function indexData(UtangUsaha $utangUsaha): array
    {
        return $utangUsaha->rekonsiliasiUtangUsaha()
            ->orderBy('TanggalFaktur')
            ->get()
            ->map(fn (RekonsiliasiUtangUsaha $item) => $this->serializeItem($item))
            ->values()
            ->all();
    }

    protected function storeValidationRules(): array
    {
        return [
            'KonfirmasiUtangUsahaID' => ['nullable', 'integer'],
            'NomorFaktur' => ['required', 'string', 'max:255'],
            'TanggalFaktur' => ['required', 'date'],
            'SaldoBuku' => ['required', 'integer'],
            'SaldoCustomer' => ['required', 'integer'],
            'Selisih' => ['required', 'integer'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function storeItem(UtangUsaha $utangUsaha, array $validated): RekonsiliasiUtangUsaha
    {
        return $utangUsaha->rekonsiliasiUtangUsaha()->create([
            'UtangUsahaID' => $utangUsaha->UtangUsahaID,
            'KonfirmasiUtangUsahaID' => $validated['KonfirmasiUtangUsahaID'] ?? null,
            'NomorFaktur' => $validated['NomorFaktur'],
            'TanggalFaktur' => $validated['TanggalFaktur'],
            'SaldoBuku' => $validated['SaldoBuku'],
            'SaldoCustomer' => $validated['SaldoCustomer'],
            'Selisih' => $validated['Selisih'],
            'Keterangan' => $validated['Keterangan'] ?? null,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UtangUsahaID' => ['required', 'integer', 'exists:utang_usaha,UtangUsahaID'],
        ]);
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $validated['UtangUsahaID']);

        return response()->json([
            'success' => true,
            'data' => $this->indexData($utangUsaha),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UtangUsahaID' => ['required', 'integer', 'exists:utang_usaha,UtangUsahaID'],
            ...$this->storeValidationRules(),
        ]);
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $validated['UtangUsahaID']);
        unset($validated['UtangUsahaID']);

        $item = $this->storeItem($utangUsaha, $validated);
        $this->rekonsiliasiCheck($utangUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi utang usaha berhasil disimpan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function update(Request $request, RekonsiliasiUtangUsaha $rekonsiliasiUtangUsaha): JsonResponse
    {
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $rekonsiliasiUtangUsaha->UtangUsahaID);

        $validated = $request->validate([
            'KonfirmasiUtangUsahaID' => ['sometimes', 'nullable', 'integer'],
            'NomorFaktur' => ['sometimes', 'string', 'max:255'],
            'TanggalFaktur' => ['sometimes', 'date'],
            'SaldoBuku' => ['sometimes', 'integer'],
            'SaldoCustomer' => ['sometimes', 'integer'],
            'Selisih' => ['sometimes', 'integer'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $rekonsiliasiUtangUsaha->update($validated);
        $this->rekonsiliasiCheck($utangUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi utang usaha berhasil diperbarui.',
            'data' => $this->serializeItem($rekonsiliasiUtangUsaha->fresh()),
        ]);
    }

    public function destroy(Request $request, RekonsiliasiUtangUsaha $rekonsiliasiUtangUsaha): JsonResponse
    {
        $utangUsaha = $this->resolveAuthorizedUtangUsaha($request, $rekonsiliasiUtangUsaha->UtangUsahaID);
        $rekonsiliasiUtangUsaha->delete();
        $this->rekonsiliasiCheck($utangUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi utang usaha berhasil dihapus.',
        ]);
    }
}
