<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\SetaraKas\JurnalKoreksiSetaraKas;
use App\Models\SetaraKas\SetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalKoreksiSetaraKasController extends Controller
{
    protected function resolveAuthorizedSetaraKas(Request $request, int $setaraKasId): SetaraKas
    {
        $setaraKas = SetaraKas::findOrFail($setaraKasId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $setaraKas->JwbKasusID)
            ->firstOrFail();

        return $setaraKas;
    }

    private function jurnalKoreksiCheck(SetaraKas $setaraKas): void
    {
        $hasJurnalKoreksi = $setaraKas->jurnalKoreksi()->exists();

        $setaraKas->updateQuietly([
            'JurnalCheck' => $hasJurnalKoreksi,
        ]);
    }

    protected function serializeItem(JurnalKoreksiSetaraKas $jurnalKoreksi): array
    {
        $jurnalKoreksi->loadMissing('pembayaranJurnalKoreksi.coa');

        $pembayaran = $jurnalKoreksi->pembayaranJurnalKoreksi
            ->sortBy('PembayaranJurnalKoreksiSetaraKasID')
            ->values();

        return [
            'JurnalKoreksiSetaraKasID' => $jurnalKoreksi->JurnalKoreksiSetaraKasID,
            'SetaraKasID' => $jurnalKoreksi->SetaraKasID,
            'Keterangan' => $jurnalKoreksi->Keterangan,
            'pembayaran' => $pembayaran->map(fn ($item) => [
                'PembayaranJurnalKoreksiSetaraKasID' => $item->PembayaranJurnalKoreksiSetaraKasID,
                'JurnalKoreksiSetaraKasID' => $item->JurnalKoreksiSetaraKasID,
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

    protected function syncPembayaran(JurnalKoreksiSetaraKas $jurnalKoreksi, array $pembayaran): void
    {
        $jurnalKoreksi->pembayaranJurnalKoreksi()->delete();
        $jurnalKoreksi->pembayaranJurnalKoreksi()->createMany($pembayaran);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
        ]);

        $setaraKas = $this->resolveAuthorizedSetaraKas($request, $validated['SetaraKasID']);

        $items = $setaraKas->jurnalKoreksi()
            ->with('pembayaranJurnalKoreksi.coa')
            ->orderBy('JurnalKoreksiSetaraKasID')
            ->get()
            ->map(fn (JurnalKoreksiSetaraKas $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
            ...$this->validationRules(),
        ]);

        $setaraKas = $this->resolveAuthorizedSetaraKas($request, $validated['SetaraKasID']);

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $setaraKas->JwbKasusID
        );

        $jurnalKoreksi = DB::transaction(function () use ($setaraKas, $validated): JurnalKoreksiSetaraKas {
            $item = $setaraKas->jurnalKoreksi()->create([
                'SetaraKasID' => $setaraKas->SetaraKasID,
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $item->pembayaranJurnalKoreksi()->createMany($validated['pembayaran']);

            $this->jurnalKoreksiCheck($setaraKas);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi setara kas berhasil disimpan.',
            'data' => $this->serializeItem($jurnalKoreksi),
        ], 201);
    }

    public function show(
        Request $request,
        JurnalKoreksiSetaraKas $jurnalKoreksiSetaraKas
    ): JsonResponse {
        $this->resolveAuthorizedSetaraKas(
            $request,
            $jurnalKoreksiSetaraKas->SetaraKasID
        );

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($jurnalKoreksiSetaraKas),
        ]);
    }

    public function update(
        Request $request,
        JurnalKoreksiSetaraKas $jurnalKoreksiSetaraKas
    ): JsonResponse {
        $setaraKas = $this->resolveAuthorizedSetaraKas(
            $request,
            $jurnalKoreksiSetaraKas->SetaraKasID
        );

        $validated = $request->validate($this->validationRules());

        $this->validatePaymentsForCase(
            $validated['pembayaran'],
            $setaraKas->JwbKasusID
        );

        DB::transaction(function () use ($jurnalKoreksiSetaraKas, $setaraKas, $validated): void {
            $jurnalKoreksiSetaraKas->update([
                'Keterangan' => $validated['Keterangan'] ?? null,
            ]);

            $this->syncPembayaran(
                $jurnalKoreksiSetaraKas,
                $validated['pembayaran']
            );

            $this->jurnalKoreksiCheck($setaraKas);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi setara kas berhasil diperbarui.',
            'data' => $this->serializeItem(
                $jurnalKoreksiSetaraKas->fresh()
            ),
        ]);
    }

    public function destroy(
        Request $request,
        JurnalKoreksiSetaraKas $jurnalKoreksiSetaraKas
    ): JsonResponse {
        $setaraKas = $this->resolveAuthorizedSetaraKas(
            $request,
            $jurnalKoreksiSetaraKas->SetaraKasID
        );

        DB::transaction(function () use ($jurnalKoreksiSetaraKas, $setaraKas): void {
            $jurnalKoreksiSetaraKas->delete();
            $this->jurnalKoreksiCheck($setaraKas);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal koreksi setara kas berhasil dihapus.',
        ]);
    }
}

