<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\SetaraKas\CekSaldoSetaraKas;
use App\Models\SetaraKas\SetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CekSaldoSetaraKasController extends Controller
{
    private function resolveAuthorizedSetaraKas(Request $request, int $setaraKasId): SetaraKas
    {
        $setaraKas = SetaraKas::findOrFail($setaraKasId);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $setaraKas->JwbKasusID)
            ->firstOrFail();

        return $setaraKas;
    }

    private function calculateSelisih(int $saldoBukuBesar, int $saldoRekeningKoran): int
    {
        return $saldoBukuBesar - $saldoRekeningKoran;
    }

    private function serialize(CekSaldoSetaraKas $item): array
    {
        return [
            'CekSaldoID' => $item->CekSaldoID,
            'SetaraKasID' => $item->SetaraKasID,
            'NamaBank' => $item->NamaBank,
            'AkunCOA' => $item->AkunCOA,
            'SaldoBukuBesar' => $item->SaldoBukuBesar,
            'SaldoRekeningKoran' => $item->SaldoRekeningKoran,
            'Selisih' => $item->Selisih,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function updateCekSaldoCheck(SetaraKas $setaraKas): void
    {
        $setaraKas->updateQuietly([
            'CekSaldoCheck' => $setaraKas->cekSaldoSetaraKas()->exists(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
        ]);
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $validated['SetaraKasID']);
        $items = $setaraKas->cekSaldoSetaraKas()
            ->orderBy('CekSaldoID')
            ->get()
            ->map(fn (CekSaldoSetaraKas $item) => $this->serialize($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = CekSaldoSetaraKas::findOrFail($id);
        $this->resolveAuthorizedSetaraKas($request, (int) $item->SetaraKasID);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
            'NamaBank' => ['required', 'string', 'max:255'],
            'AkunCOA' => ['required', 'string', 'max:255'],
            'SaldoBukuBesar' => ['required', 'integer'],
            'SaldoRekeningKoran' => ['required', 'integer'],
        ]);
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $validated['SetaraKasID']);
        $validated['Selisih'] = $this->calculateSelisih((int) $validated['SaldoBukuBesar'], (int) $validated['SaldoRekeningKoran']);
        $item = $setaraKas->cekSaldoSetaraKas()->create($validated);
        $this->updateCekSaldoCheck($setaraKas);

        return response()->json([
            'success' => true,
            'message' => 'Data cek saldo berhasil disimpan.',
            'data' => $this->serialize($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = CekSaldoSetaraKas::findOrFail($id);
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $item->SetaraKasID);
        $validated = $request->validate([
            'NamaBank' => ['sometimes', 'string', 'max:255'],
            'AkunCOA' => ['sometimes', 'string', 'max:255'],
            'SaldoBukuBesar' => ['sometimes', 'integer'],
            'SaldoRekeningKoran' => ['sometimes', 'integer'],
        ]);

        $saldoBukuBesar = (int) ($validated['SaldoBukuBesar'] ?? $item->SaldoBukuBesar);
        $saldoRekeningKoran = (int) ($validated['SaldoRekeningKoran'] ?? $item->SaldoRekeningKoran);
        $validated['Selisih'] = $this->calculateSelisih(
            $saldoBukuBesar,
            $saldoRekeningKoran
        );

        $item->update($validated);
        $this->updateCekSaldoCheck($setaraKas);

        return response()->json([
            'success' => true,
            'message' => 'Data cek saldo berhasil diperbarui.',
            'data' => $this->serialize($item->fresh()),
        ]);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.CekSaldoID' => [
                'nullable',
                'integer',
                'exists:cek_saldo_setara_kas,CekSaldoID',
                'distinct',
            ],
            'rows.*.NamaBank' => ['required', 'string', 'max:255'],
            'rows.*.AkunCOA' => ['required', 'string', 'max:255'],
            'rows.*.SaldoBukuBesar' => ['required', 'integer'],
            'rows.*.SaldoRekeningKoran' => ['required', 'integer'],
        ]);
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $validated['SetaraKasID']);
        $ids = collect($validated['rows'])
            ->pluck('CekSaldoID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $existing = $setaraKas->cekSaldoSetaraKas()
            ->whereIn('CekSaldoID', $ids)
            ->get()
            ->keyBy('CekSaldoID');

        foreach ($ids as $id) {
            if (! $existing->has($id)) {
                throw ValidationException::withMessages([
                    'rows' => "CekSaldoID {$id} bukan milik Setara Kas ini.",
                ]);
            }
        }

        $saved = DB::transaction(function () use ($validated, $existing, $setaraKas) {
            $result = [];

            foreach ($validated['rows'] as $row) {
                $data = [
                    'NamaBank' => $row['NamaBank'],
                    'AkunCOA' => $row['AkunCOA'],
                    'SaldoBukuBesar' => $row['SaldoBukuBesar'],
                    'SaldoRekeningKoran' => $row['SaldoRekeningKoran'],
                    'Selisih' => $this->calculateSelisih(
                        (int) $row['SaldoBukuBesar'],
                        (int) $row['SaldoRekeningKoran']
                    ),
                ];

                if (! empty($row['CekSaldoID'])) {
                    $item = $existing->get((int) $row['CekSaldoID']);
                    $item->update($data);
                } else {
                    $item = $setaraKas->cekSaldoSetaraKas()->create($data);
                }

                $result[] = $item->fresh();
            }

            $this->updateCekSaldoCheck($setaraKas);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data cek saldo berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (CekSaldoSetaraKas $item) => $this->serialize($item))
                ->values(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = CekSaldoSetaraKas::findOrFail($id);
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $item->SetaraKasID);
        $item->delete();
        $this->updateCekSaldoCheck($setaraKas);

        return response()->json([
            'success' => true,
            'message' => 'Data cek saldo berhasil dihapus.',
        ]);
    }
}
