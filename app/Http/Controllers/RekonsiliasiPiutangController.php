<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\KonfirmasiPiutang;
use App\Models\Piutang;
use App\Models\RekonsiliasiPiutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekonsiliasiPiutangController extends Controller
{
    protected function resolveAuthorizedPiutang(Request $request, int $piutangId): Piutang
    {
        $piutang = Piutang::findOrFail($piutangId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        return $piutang;
    }

    private function rekonsiliasiCheck(Piutang $piutang): void
    {
        $hasRekonsiliasi = $piutang->rekonsiliasiPiutang()->exists();

        $piutang->updateQuietly([
            'RekonsiliasiCheck' => $hasRekonsiliasi,
        ]);
    }

    protected function customerOptions(Piutang $piutang): array
    {
        return $piutang->konfirmasiPiutang()
            ->orderBy('KonfirmasiPiutangID')
            ->get(['KonfirmasiPiutangID', 'NamaCustomer'])
            ->map(fn (KonfirmasiPiutang $konfirmasiPiutang) => [
                'value' => $konfirmasiPiutang->KonfirmasiPiutangID,
                'label' => $konfirmasiPiutang->NamaCustomer,
            ])
            ->values()
            ->all();
    }

    protected function resolveKonfirmasiPiutang(Piutang $piutang, int $konfirmasiPiutangId): KonfirmasiPiutang
    {
        return $piutang->konfirmasiPiutang()
            ->where('KonfirmasiPiutangID', $konfirmasiPiutangId)
            ->firstOrFail();
    }

    protected function serializeItem(RekonsiliasiPiutang $item): array
    {
        $item->loadMissing('konfirmasiPiutang');

        return [
            'RekonsiliasiPiutangID' => $item->RekonsiliasiPiutangID,
            'PiutangID' => $item->PiutangID,
            'KonfirmasiPiutangID' => $item->KonfirmasiPiutangID,
            'NamaCustomer' => $item->konfirmasiPiutang?->NamaCustomer,
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

    protected function indexData(Piutang $piutang): array
    {
        return $piutang->rekonsiliasiPiutang()
            ->with('konfirmasiPiutang')
            ->orderBy('TanggalFaktur')
            ->get()
            ->map(fn (RekonsiliasiPiutang $item) => $this->serializeItem($item))
            ->values()
            ->all();
    }

    protected function storeItem(Piutang $piutang, array $validated): RekonsiliasiPiutang
    {
        $konfirmasiPiutang = $this->resolveKonfirmasiPiutang(
            $piutang,
            $validated['KonfirmasiPiutangID']
        );

        return $piutang->rekonsiliasiPiutang()->create([
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'NomorFaktur' => $validated['NomorFaktur'],
            'TanggalFaktur' => $validated['TanggalFaktur'],
            'SaldoBuku' => $validated['SaldoBuku'],
            'SaldoCustomer' => $validated['SaldoCustomer'],
            'Selisih' => $validated['Selisih'],
            'Keterangan' => $validated['Keterangan'] ?? null,
        ]);
    }

    protected function storeValidationRules(): array
    {
        return [
            'KonfirmasiPiutangID' => ['required', 'integer', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'NomorFaktur' => ['required', 'string', 'max:255'],
            'TanggalFaktur' => ['required', 'date'],
            'SaldoBuku' => ['required', 'integer'],
            'SaldoCustomer' => ['required', 'integer'],
            'Selisih' => ['required', 'integer'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);

        return response()->json([
            'success' => true,
            'data' => $this->indexData($piutang),
            'customer_options' => $this->customerOptions($piutang),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
            ...$this->storeValidationRules(),
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);
        unset($validated['PiutangID']);

        $item = $this->storeItem($piutang, $validated);
        $this->rekonsiliasiCheck($piutang);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil disimpan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function update(Request $request, RekonsiliasiPiutang $rekonsiliasiPiutang): JsonResponse
    {
        $piutang = $this->resolveAuthorizedPiutang($request, $rekonsiliasiPiutang->PiutangID);

        $validated = $request->validate([
            'KonfirmasiPiutangID' => ['sometimes', 'integer', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'NomorFaktur' => ['sometimes', 'string', 'max:255'],
            'TanggalFaktur' => ['sometimes', 'date'],
            'SaldoBuku' => ['sometimes', 'integer'],
            'SaldoCustomer' => ['sometimes', 'integer'],
            'Selisih' => ['sometimes', 'integer'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('KonfirmasiPiutangID', $validated)) {
            $validated['KonfirmasiPiutangID'] = $this->resolveKonfirmasiPiutang(
                $piutang,
                $validated['KonfirmasiPiutangID']
            )->KonfirmasiPiutangID;
        }

        $rekonsiliasiPiutang->update($validated);
        $this->rekonsiliasiCheck($piutang);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil diperbarui.',
            'data' => $this->serializeItem($rekonsiliasiPiutang->fresh()),
        ]);
    }

    public function destroy(Request $request, RekonsiliasiPiutang $rekonsiliasiPiutang): JsonResponse
    {
        $piutang = $this->resolveAuthorizedPiutang($request, $rekonsiliasiPiutang->PiutangID);
        $rekonsiliasiPiutang->delete();
        $this->rekonsiliasiCheck($piutang);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil dihapus.',
        ]);
    }
}
