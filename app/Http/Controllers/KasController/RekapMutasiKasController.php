<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Kas\IsiRekapMutasiKas;
use App\Models\Kas\Kas;
use App\Models\RekapMutasiKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekapMutasiKasController extends Controller
{
    private function resolveAuthorizedKas(Request $request, int $kasId): Kas
    {
        $kas = Kas::findOrFail($kasId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $kas->JwbKasusID)
            ->firstOrFail();

        return $kas;
    }

    private function resolveAuthorizedRekap(
        Request $request,
        int $rekapMutasiId
    ): RekapMutasiKas {
        $rekap = RekapMutasiKas::with('kas')->findOrFail($rekapMutasiId);
        $this->resolveAuthorizedKas($request, (int) $rekap->KasID);

        return $rekap;
    }

    private function serializeIsi(IsiRekapMutasiKas $item): array
    {
        return [
            'IsiRekapMutasiID' => $item->IsiRekapMutasiID,
            'RekapMutasiID' => $item->RekapMutasiID,
            'Tanggal' => $item->Tanggal?->format('Y-m-d'),
            'Keterangan' => $item->Keterangan,
            'Debit' => $item->Debit,
            'Kredit' => $item->Kredit,
            'Saldo' => $item->Saldo,
        ];
    }

    private function serializeRekap(RekapMutasiKas $rekap): array
    {
        return [
            'RekapMutasiID' => $rekap->RekapMutasiID,
            'KasID' => $rekap->KasID,
            'SaldoAwal' => $rekap->SaldoAwal,
            'DebitTotal' => $rekap->DebitTotal,
            'KreditTotal' => $rekap->KreditTotal,
            'isiRekapMutasi' => $rekap->isiRekapMutasi
                ->map(fn (IsiRekapMutasiKas $item) => $this->serializeIsi($item))
                ->values(),
            'created_at' => $rekap->created_at,
            'updated_at' => $rekap->updated_at,
        ];
    }

    private function calculateRows(int $saldoAwal, array $rows): array
    {
        $saldo = $saldoAwal;
        $debitTotal = 0;
        $kreditTotal = 0;
        $calculatedRows = [];

        foreach ($rows as $row) {
            $debit = (int) ($row['Debit'] ?? 0);
            $kredit = (int) ($row['Kredit'] ?? 0);
            $saldo += $debit - $kredit;
            $debitTotal += $debit;
            $kreditTotal += $kredit;

            $calculatedRows[] = [
                'Tanggal' => $row['Tanggal'],
                'Keterangan' => $row['Keterangan'] ?? null,
                'Debit' => $debit,
                'Kredit' => $kredit,
                'Saldo' => $saldo,
            ];
        }

        return [$calculatedRows, $debitTotal, $kreditTotal];
    }

    private function recalculate(RekapMutasiKas $rekap): void
    {
        $rows = $rekap->isiRekapMutasi()
            ->orderBy('IsiRekapMutasiID')
            ->get();
        $saldo = (int) $rekap->SaldoAwal;
        $debitTotal = 0;
        $kreditTotal = 0;

        foreach ($rows as $row) {
            $debitTotal += (int) $row->Debit;
            $kreditTotal += (int) $row->Kredit;
            $saldo += (int) $row->Debit - (int) $row->Kredit;
            $row->update(['Saldo' => $saldo]);
        }

        $rekap->update([
            'DebitTotal' => $debitTotal,
            'KreditTotal' => $kreditTotal,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'KasID' => ['required', 'integer', 'exists:kas,KasID'],
        ]);
        $kas = $this->resolveAuthorizedKas($request, (int) $validated['KasID']);
        $items = $kas->rekapMutasi()
            ->with(['isiRekapMutasi' => fn ($query) => $query->orderBy('IsiRekapMutasiID')])
            ->orderBy('RekapMutasiID')
            ->get()
            ->map(fn (RekapMutasiKas $rekap) => $this->serializeRekap($rekap))
            ->values();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'KasID' => ['required', 'integer', 'exists:kas,KasID'],
            'SaldoAwal' => ['sometimes', 'integer'],
        ]);
        $kas = $this->resolveAuthorizedKas($request, (int) $validated['KasID']);

        $rekap = $kas->rekapMutasi()->create([
            'SaldoAwal' => (int) ($validated['SaldoAwal'] ?? 0),
            'DebitTotal' => 0,
            'KreditTotal' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekap mutasi kas berhasil dibuat.',
            'data' => $this->serializeRekap($rekap->load('isiRekapMutasi')),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $rekap = $this->resolveAuthorizedRekap($request, $id)
            ->load(['isiRekapMutasi' => fn ($query) => $query->orderBy('IsiRekapMutasiID')]);

        return response()->json([
            'success' => true,
            'data' => $this->serializeRekap($rekap),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rekap = $this->resolveAuthorizedRekap($request, $id);
        $validated = $request->validate([
            'SaldoAwal' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($rekap, $validated): void {
            $rekap->update(['SaldoAwal' => $validated['SaldoAwal']]);
            $this->recalculate($rekap);
        });

        return response()->json([
            'success' => true,
            'message' => 'Saldo awal rekap mutasi berhasil diperbarui.',
            'data' => $this->serializeRekap(
                $rekap->fresh()->load(['isiRekapMutasi' => fn ($query) => $query->orderBy('IsiRekapMutasiID')])
            ),
        ]);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'KasID' => ['required', 'integer', 'exists:kas,KasID'],
            'RekapMutasiID' => ['nullable', 'integer', 'exists:rekap_mutasi_kas,RekapMutasiID'],
            'SaldoAwal' => ['required', 'integer'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.IsiRekapMutasiID' => [
                'nullable',
                'integer',
                'exists:isi_rekap_mutasi_kas,IsiRekapMutasiID',
                'distinct',
            ],
            'rows.*.Tanggal' => ['required', 'date'],
            'rows.*.Keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.Debit' => ['nullable', 'integer'],
            'rows.*.Kredit' => ['nullable', 'integer'],
        ]);

        $kas = $this->resolveAuthorizedKas($request, (int) $validated['KasID']);
        $rekap = null;
        if (! empty($validated['RekapMutasiID'])) {
            $rekap = RekapMutasiKas::where('KasID', $kas->KasID)
                ->findOrFail($validated['RekapMutasiID']);
        }

        $detailIds = collect($validated['rows'])
            ->pluck('IsiRekapMutasiID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->values();
        $existingRows = collect();
        if ($detailIds->isNotEmpty()) {
            $existingRows = IsiRekapMutasiKas::where('RekapMutasiID', $rekap?->RekapMutasiID)
                ->whereIn('IsiRekapMutasiID', $detailIds)
                ->get()
                ->keyBy('IsiRekapMutasiID');

            if ($rekap === null || $existingRows->count() !== $detailIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'rows' => 'Isi rekap yang dikirim bukan milik Rekap Mutasi ini.',
                ]);
            }
        }

        [$calculatedRows, $debitTotal, $kreditTotal] = $this->calculateRows(
            (int) $validated['SaldoAwal'],
            $validated['rows']
        );

        $saved = DB::transaction(function () use (
            $kas,
            $rekap,
            $validated,
            $calculatedRows,
            $debitTotal,
            $kreditTotal,
            $existingRows
        ): RekapMutasiKas {
            $parent = $rekap ?? $kas->rekapMutasi()->create([
                'SaldoAwal' => $validated['SaldoAwal'],
                'DebitTotal' => 0,
                'KreditTotal' => 0,
            ]);
            $parent->update([
                'SaldoAwal' => $validated['SaldoAwal'],
                'DebitTotal' => $debitTotal,
                'KreditTotal' => $kreditTotal,
            ]);

            foreach ($validated['rows'] as $index => $row) {
                $payload = $calculatedRows[$index];
                if (! empty($row['IsiRekapMutasiID'])) {
                    $existingRows->get((int) $row['IsiRekapMutasiID'])->update($payload);
                } else {
                    $parent->isiRekapMutasi()->create($payload);
                }
            }

            return $parent->fresh()->load([
                'isiRekapMutasi' => fn ($query) => $query->orderBy('IsiRekapMutasiID'),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Rekap mutasi kas berhasil disimpan.',
            'data' => $this->serializeRekap($saved),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $rekap = $this->resolveAuthorizedRekap($request, $id);
        $rekap->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rekap mutasi kas berhasil dihapus.',
        ]);
    }
}
