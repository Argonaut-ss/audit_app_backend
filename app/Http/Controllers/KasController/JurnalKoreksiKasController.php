<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\Kas\JurnalKoreksiKas;
use App\Models\Kas\Kas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiKasController extends Controller
{
    protected function resolveAuthorizedKas(Request $request, int $kasId): Kas
    {
        $kas = Kas::findOrFail($kasId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $kas->JwbKasusID)
            ->firstOrFail();

        return $kas;
    }

    private function jurnalKoreksiCheck(Kas $kas): void
    {
        $hasJurnalKoreksi = $kas->jurnalKoreksi()->exists();

        $kas->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiKas $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiKasID')
            ->values();

        return [
            'JurnalKoreksiKasID' => $jurnalKoreksi->JurnalKoreksiKasID,
            'KasID' => $jurnalKoreksi->KasID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiKasID' => $item->PembayaranJurnalKoreksiKasID,
                'JurnalKoreksiKasID' => $item->JurnalKoreksiKasID,
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

    protected function syncPembayaran(JurnalKoreksiKas $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'KasID' => ['required', 'integer', 'exists:kas,KasID'],
        ]);

        $kas = $this->resolveAuthorizedKas($request, $validated['KasID']);

        $items = $kas->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiKasID')
            ->get()
            ->map(fn (JurnalKoreksiKas $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'KasID' => ['required', 'integer', 'exists:kas,KasID'],
            ...$this->validationRules(),
        ]);

        $kas = $this->resolveAuthorizedKas($request, $validated['KasID']);

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $kas->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($kas, $validated): JurnalKoreksiKas {
            $item = $kas->jurnalKoreksi()->create([
                'KasID' => $kas->KasID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($kas);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi kas berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function update(
        Request $request,
        JurnalKoreksiKas $jurnalKoreksiKas
    ): JsonResponse {
        $kas = $this->resolveAuthorizedKas(
            $request,
            $jurnalKoreksiKas->KasID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $kas->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiKas, $kas, $validated): void {
            $jurnalKoreksiKas->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiKas,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($kas);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi kas berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiKas->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiKas $jurnalKoreksiKas
    ): JsonResponse {
        $kas = $this->resolveAuthorizedKas(
            $request,
            $jurnalKoreksiKas->KasID
        );

        DB::transaction(function () use ($jurnalKoreksiKas, $kas): void {
            $jurnalKoreksiKas->delete();
            $this->jurnalKoreksiCheck($kas);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi kas berhasil dihapus.',
        ]);
    }
}
