<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\Persediaan\Persediaan;
use App\Models\Persediaan\StokOpnamePersediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokOpnamePersediaanController extends Controller
{
    /**
     * Set flag StockCheck di induk Persediaan berdasarkan ada/tidaknya baris stok opname.
     */
    private function stockCheck(int $persediaanId): void
    {
        $persediaan = Persediaan::find($persediaanId);

        if (! $persediaan) {
            return;
        }

        $persediaan->updateQuietly([
            'StockCheck' => $persediaan->stokOpname()->exists(),
        ]);
    }

    /**
     * Hitung selisih di backend (source of truth). Frontend tidak boleh menentukan selisih.
     *
     * SelisihFisik  = JumlahFisik  - JumlahSistem
     * SelisihSistem = JumlahSistem - SaldoNeraca
     */
    private function computeSelisih(array $row): array
    {
        $saldoNeraca = (int) ($row['SaldoNeraca'] ?? 0);
        $jumlahSistem = (int) ($row['JumlahSistem'] ?? 0);
        $jumlahFisik = (int) ($row['JumlahFisik'] ?? 0);

        return [
            'SaldoNeraca' => $saldoNeraca,
            'JumlahSistem' => $jumlahSistem,
            'JumlahFisik' => $jumlahFisik,
            'SelisihFisik' => $jumlahFisik - $jumlahSistem,
            'SelisihSistem' => $jumlahSistem - $saldoNeraca,
        ];
    }

    /**
     * HOOK AUTO-CREATE TURUNAN (dikerjakan oleh pemilik modul Uji Mutasi & Test Pricing).
     *
     * Tiap baris StokOpname yang BARU dibuat harus otomatis membuat:
     *   - 1 baris UjiMutasi   (relasi 1:1, referensi StokOpnameID)
     *   - 1 baris TestPricing (relasi 1:banyak, baris pertama, referensi StokOpnameID)
     *
     * Catatan implementasi:
     *   - Panggil di DALAM transaksi store (sudah disediakan di bawah).
     *   - Cukup urus CREATE. Penghapusan turunan sudah otomatis lewat cascade FK
     *     (StokOpnameID di tabel uji_mutasi & test_pricing pakai cascadeOnDelete).
     *   - Nama persediaan TIDAK perlu disalin; ambil via relasi ke StokOpname (StokOpnameID).
     *
     * Sengaja dibiarkan kosong sampai tabel & model UjiMutasi/TestPricing tersedia.
     */
    private function syncTurunan(StokOpnamePersediaan $stokOpname): void
    {
        // TODO(UjiMutasi & TestPricing):
        //   $stokOpname->ujiMutasi()->create([... 'StokOpnameID' => $stokOpname->StokOpnameID ...]);
        //   $stokOpname->testPricing()->create([... 'StokOpnameID' => $stokOpname->StokOpnameID ...]);
    }

    protected function resolveAuthorizedPersediaan(Request $request, int $persediaanId): Persediaan
    {
        $persediaan = Persediaan::findOrFail($persediaanId);

        \App\Models\JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        return $persediaan;
    }

    protected function serializeItem(StokOpnamePersediaan $item): array
    {
        return [
            'StokOpnameID' => $item->StokOpnameID,
            'PersediaanID' => $item->PersediaanID,
            'NamaPersediaan' => $item->NamaPersediaan,
            'Satuan' => $item->Satuan,
            'SaldoNeraca' => $item->SaldoNeraca,
            'JumlahSistem' => $item->JumlahSistem,
            'JumlahFisik' => $item->JumlahFisik,
            'SelisihFisik' => $item->SelisihFisik,
            'SelisihSistem' => $item->SelisihSistem,
            'Keterangan' => $item->Keterangan,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    /**
     * GET /api/stok-opname-persediaan?PersediaanID=...
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
        ]);

        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $validated['PersediaanID']
        );

        $items = $persediaan->stokOpname()
            ->orderBy('StokOpnameID')
            ->get()
            ->map(fn (StokOpnamePersediaan $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * POST /api/stok-opname-persediaan
     *
     * Bulk save: kirim seluruh baris tabel sekaligus.
     * - id null   -> CREATE (memicu syncTurunan)
     * - id exists -> UPDATE
     * Selisih selalu dihitung ulang backend.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['nullable', 'integer', 'exists:stok_opname_persediaan,StokOpnameID'],
            'rows.*.NamaPersediaan' => ['nullable', 'string', 'max:255'],
            'rows.*.Satuan' => ['nullable', 'string', 'max:255'],
            'rows.*.SaldoNeraca' => ['nullable', 'integer'],
            'rows.*.JumlahSistem' => ['nullable', 'integer'],
            'rows.*.JumlahFisik' => ['nullable', 'integer'],
            'rows.*.Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $validated['PersediaanID']
        );

        // Pastikan setiap id yang dikirim benar-benar milik Persediaan ini.
        $ids = collect($validated['rows'])
            ->pluck('id')
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existing = StokOpnamePersediaan::whereIn('StokOpnameID', $ids)
            ->get()
            ->keyBy('StokOpnameID');

        foreach ($ids as $id) {
            $row = $existing->get($id);

            if (! $row) {
                return response()->json([
                    'message' => "Stok opname ID {$id} tidak ditemukan.",
                ], 422);
            }

            if ((int) $row->PersediaanID !== (int) $persediaan->PersediaanID) {
                return response()->json([
                    'message' => "Stok opname ID {$id} bukan milik Persediaan ini.",
                ], 422);
            }
        }

        $saved = DB::transaction(function () use ($validated, $persediaan, $existing) {
            $result = [];

            foreach ($validated['rows'] as $row) {
                $computed = $this->computeSelisih($row);

                $payload = [
                    'NamaPersediaan' => $row['NamaPersediaan'] ?? null,
                    'Satuan' => $row['Satuan'] ?? null,
                    'SaldoNeraca' => $computed['SaldoNeraca'],
                    'JumlahSistem' => $computed['JumlahSistem'],
                    'JumlahFisik' => $computed['JumlahFisik'],
                    'SelisihFisik' => $computed['SelisihFisik'],
                    'SelisihSistem' => $computed['SelisihSistem'],
                    'Keterangan' => $row['Keterangan'] ?? null,
                ];

                if (! is_null($row['id'] ?? null)) {
                    $item = $existing->get((int) $row['id']);
                    $item->update($payload);
                } else {
                    $item = $persediaan->stokOpname()->create(array_merge(
                        ['PersediaanID' => $persediaan->PersediaanID],
                        $payload
                    ));

                    // Auto-create turunan (UjiMutasi & TestPricing) hanya untuk baris baru.
                    $this->syncTurunan($item);
                }

                $result[] = $item->fresh();
            }

            $this->stockCheck((int) $persediaan->PersediaanID);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data stok opname berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (StokOpnamePersediaan $item) => $this->serializeItem($item))
                ->values(),
        ], 200);
    }

    /**
     * PUT /api/stok-opname-persediaan/{stokOpnamePersediaan}
     */
    public function update(
        Request $request,
        StokOpnamePersediaan $stokOpnamePersediaan
    ): JsonResponse {
        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $stokOpnamePersediaan->PersediaanID
        );

        $validated = $request->validate([
            'NamaPersediaan' => ['nullable', 'string', 'max:255'],
            'Satuan' => ['nullable', 'string', 'max:255'],
            'SaldoNeraca' => ['nullable', 'integer'],
            'JumlahSistem' => ['nullable', 'integer'],
            'JumlahFisik' => ['nullable', 'integer'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $computed = $this->computeSelisih($validated);

        $stokOpnamePersediaan->update([
            'NamaPersediaan' => $validated['NamaPersediaan'] ?? null,
            'Satuan' => $validated['Satuan'] ?? null,
            'SaldoNeraca' => $computed['SaldoNeraca'],
            'JumlahSistem' => $computed['JumlahSistem'],
            'JumlahFisik' => $computed['JumlahFisik'],
            'SelisihFisik' => $computed['SelisihFisik'],
            'SelisihSistem' => $computed['SelisihSistem'],
            'Keterangan' => $validated['Keterangan'] ?? null,
        ]);

        $this->stockCheck((int) $persediaan->PersediaanID);

        return response()->json([
            'success' => true,
            'message' => 'Data stok opname berhasil diperbarui.',
            'data' => $this->serializeItem($stokOpnamePersediaan->fresh()),
        ]);
    }

    /**
     * DELETE /api/stok-opname-persediaan/{stokOpnamePersediaan}
     */
    public function destroy(
        Request $request,
        StokOpnamePersediaan $stokOpnamePersediaan
    ): JsonResponse {
        $persediaan = $this->resolveAuthorizedPersediaan(
            $request,
            $stokOpnamePersediaan->PersediaanID
        );

        DB::transaction(function () use ($stokOpnamePersediaan, $persediaan): void {
            // Turunan (UjiMutasi & TestPricing) terhapus otomatis via cascade FK.
            $stokOpnamePersediaan->delete();
            $this->stockCheck((int) $persediaan->PersediaanID);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data stok opname berhasil dihapus.',
        ]);
    }
}
