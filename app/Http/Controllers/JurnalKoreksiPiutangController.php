<?php

namespace App\Http\Controllers;

use App\Models\COA;
use App\Models\JurnalKoreksi;
use App\Models\JwbKasus;
use App\Models\Piutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiPiutangController extends Controller
{
    protected function resolveAuthorizedPiutang(Request $request, int $piutangId): Piutang
    {
        $piutang = Piutang::findOrFail($piutangId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        return $piutang;
    }

    protected function serializeItem(JurnalKoreksi $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');
        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiID')
            ->values();

        return [
            'JurnalKoreksiID' => $jurnalKoreksi->JurnalKoreksiID,
            'PiutangID' => $jurnalKoreksi->PiutangID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiID' => $item->PembayaranJurnalKoreksiID,
                'JurnalKoreksiID' => $item->JurnalKoreksiID,
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
        $totalDebet = 0;
        $totalKredit = 0;

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

            $totalDebet += $debet;
            $totalKredit += $kredit;
        }

        if ($totalDebet !== $totalKredit) {
            abort(422, 'Total Debet dan Total Kredit harus sama.');
        }
    }

    protected function syncPembayaran(JurnalKoreksi $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);
        $items = $piutang->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiID')
            ->get()
            ->map(fn (JurnalKoreksi $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'integer', 'exists:Piutang,PiutangID'],
            ...$this->validationRules(),
        ]);
        $piutang = $this->resolveAuthorizedPiutang($request, $validated['PiutangID']);
        $this->validatePaymentsForCase($validated['pembayaran'], $piutang->JwbKasusID);

        $jurnalKoreksi = DB::transaction(function () use ($piutang, $validated): JurnalKoreksi {
            $item = $piutang->jurnalKoreksi()->create([
                'PiutangID' => $piutang->PiutangID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);
            $piutang->updateQuietly(['JurnalCheck' => true]);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi piutang berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(Request $request, JurnalKoreksi $jurnalKoreksi): JsonResponse
    {
        $piutang = $this->resolveAuthorizedPiutang($request, $jurnalKoreksi->PiutangID);
        $validated = $request->validate($this->validationRules());
        $this->validatePaymentsForCase($validated['pembayaran'], $piutang->JwbKasusID);

        DB::transaction(function () use ($jurnalKoreksi, $validated): void {
            $jurnalKoreksi->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);
            $this->syncPembayaran($jurnalKoreksi, $validated['pembayaran']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi piutang berhasil diperbarui.',
            'data' => $this->serializeItem($jurnalKoreksi->fresh()),
        ]);
    }

    public function destroy(Request $request, JurnalKoreksi $jurnalKoreksi): JsonResponse
    {
        $piutang = $this->resolveAuthorizedPiutang($request, $jurnalKoreksi->PiutangID);

        DB::transaction(function () use ($jurnalKoreksi, $piutang): void {
            $jurnalKoreksi->delete();
            $piutang->updateQuietly([
                'JurnalCheck' => $piutang->jurnalKoreksi()->exists(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi piutang berhasil dihapus.',
        ]);
    }
}
