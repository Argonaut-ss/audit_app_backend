<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Persediaan\Persediaan;
use App\Models\Persediaan\StokOpnamePersediaan;
use App\Models\Persediaan\TestPricingPersediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TestPricingPersediaanController extends Controller
{
    protected function resolveAuthorizedStokOpname(Request $request, int $stokOpnameId, ?int $persediaanId = null): StokOpnamePersediaan {
        $stokOpname = StokOpnamePersediaan::with('persediaan')
            ->findOrFail($stokOpnameId);

        if($persediaanId !== null && (int) $stokOpname->PersediaanID !== $persediaanId){
            abort(422, 'Stok opname bukan milik Persediaan ini.');
        }

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $stokOpname->persediaan->JwbKasusID)
            ->firstOrFail();

        return $stokOpname;
    }

    protected function validationRules(): array
    {
        return[
            'HargaAudit' => ['nullable', 'integer'],
            'KuantitasAudit' => ['nullable', 'integer'],
            'HargaPerusahaan' => ['nullable', 'integer'],
            'KuantitasPerusahaan' => ['nullable', 'integer'],
        ];
    }

    protected function calculate(array $data): array
    {
        $hargaAudit = (int) ($data['HargaAudit'] ?? 0);
        $kuantitasAudit = (int) ($data['KuantitasAudit'] ?? 0);
        $hargaPerusahaan = (int) ($data['HargaPerusahaan'] ?? 0);
        $kuantitasPerusahaan = (int) ($data['KuantitasPerusahaan'] ?? 0);
        $jumlahAudit = $hargaAudit * $kuantitasAudit;
        $jumlahPerusahaan = $hargaPerusahaan * $kuantitasPerusahaan;

        return [
            'HargaAudit' => $hargaAudit,
            'KuantitasAudit' => $kuantitasAudit,
            'JumlahAudit' => $jumlahAudit,
            'HargaPerusahaan' => $hargaPerusahaan,
            'KuantitasPerusahaan' => $kuantitasPerusahaan,
            'JumlahPerusahaan' => $jumlahPerusahaan,
            'Selisih' => $jumlahAudit - $jumlahPerusahaan,
        ];
    }

    protected function serializeItem(TestPricingPersediaan $item): array
    {
        $stokOpname = $item->stokOpname;

        return [
            'TestPricingID' => $item->TestPricingID,
            'PersediaanID' => $item->PersediaanID,
            'StokOpnameID' => $item->StokOpnameID,
            'NamaPersediaan' => $stokOpname?->NamaPersediaan,
            'Satuan' => $stokOpname?->Satuan,
            'HargaAudit' => $item->HargaAudit,
            'KuantitasAudit' => $item->KuantitasAudit,
            'JumlahAudit' => $item->JumlahAudit,
            'HargaPerusahaan' => $item->HargaPerusahaan,
            'KuantitasPerusahaan' => $item->KuantitasPerusahaan,
            'JumlahPerusahaan' => $item->JumlahPerusahaan,
            'Selisih' => $item->Selisih,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
        ]);

        $persediaan = Persediaan::findOrFail($validated['PersediaanID']);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        $items = $persediaan->testPricing()
            ->with('stokOpname')
            ->orderBy('TestPricingID')
            ->get()
            ->map(fn (TestPricingPersediaan $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => ['required', 'integer', 'exists:persediaan,PersediaanID'],
            'StokOpnameID' => ['required', 'integer', 'exists:stok_opname_persediaan,StokOpnameID'],
            ...$this->validationRules(),
        ]);

        $stokOpname = $this->resolveAuthorizedStokOpname(
            $request,
            (int) $validated['StokOpnameID'],
            (int) $validated['PersediaanID']
        );

        $item = TestPricingPersediaan::create(array_merge(
            [
                'PersediaanID' => $stokOpname->PersediaanID,
                'StokOpnameID' => $stokOpname->StokOpnameID,
            ],
            $this->calculate($validated)
        ));

        return response()->json([
            'success' => true,
            'message' => 'Data test pricing berhasil disimpan.',
            'data' => $this->serializeItem($item->load('stokOpname')),
        ], 201);
    }

    public function show(Request $request, TestPricingPersediaan $test_pricing_persediaan): JsonResponse {
        $this->resolveAuthorizedStokOpname(
            $request,
            (int) $test_pricing_persediaan->StokOpnameID,
            (int) $test_pricing_persediaan->PersediaanID
        );

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($test_pricing_persediaan->load('stokOpname')),
        ]);
    }

    public function update(Request $request,TestPricingPersediaan $test_pricing_persediaan): JsonResponse {
        $this->resolveAuthorizedStokOpname(
            $request,
            (int) $test_pricing_persediaan->StokOpnameID,
            (int) $test_pricing_persediaan->PersediaanID
        );

        $validated = $request->validate(
            $this->validationRules()
        );

        $test_pricing_persediaan->update(
            $this->calculate($validated)
        );

        return response()->json([
            'success' => true,
            'message' => 'Data test pricing berhasil diperbarui.',
            'data' => $this->serializeItem(
                $test_pricing_persediaan
                    ->fresh()
                    ->load('stokOpname')
            ),
        ]);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PersediaanID' => [
                'required',
                'integer',
                'exists:persediaan,PersediaanID',
            ],

            'rows' => [
                'required',
                'array',
                'min:1',
            ],

            'rows.*.TestPricingID' => [
                'nullable',
                'integer',
                'exists:test_pricing_persediaan,TestPricingID',
            ],

            'rows.*.StokOpnameID' => [
                'required',
                'integer',
                'exists:stok_opname_persediaan,StokOpnameID',
            ],

            'rows.*.HargaAudit' => [
                'nullable',
                'integer',
            ],

            'rows.*.KuantitasAudit' => [
                'nullable',
                'integer',
            ],

            'rows.*.HargaPerusahaan' => [
                'nullable',
                'integer',
            ],

            'rows.*.KuantitasPerusahaan' => [
                'nullable',
                'integer',
            ],
        ]);
    
        $persediaan = Persediaan::findOrFail($validated['PersediaanID']);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $persediaan->JwbKasusID)
            ->firstOrFail();

        $testPricingIds = collect($validated['rows'])
            ->pluck('TestPricingID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingItems = collect();
        if ($testPricingIds->isNotEmpty()) {
            $existingItems = TestPricingPersediaan::query()
                ->whereIn('TestPricingID', $testPricingIds)
                ->get()
                ->keyBy('TestPricingID');
        }

        foreach ($validated['rows'] as $index => $row) {
            if (isset($row['TestPricingID']) && $row['TestPricingID'] !== null){
                $existing = $existingItems->get((int) $row['TestPricingID']);
                if (!$existing) {
                    throw ValidationException::withMessages([
                        "rows.$index.TestPricingID" => 'Test pricing tidak ditemukan.',
                    ]);
                }

                if ((int) $existing->PersediaanID !==(int) $persediaan->PersediaanID){
                    throw ValidationException::withMessages([
                        "rows.$index.TestPricingID" => 'Test pricing bukan milik Persediaan ini.',
                    ]);
                }
            }

            $stokOpname = StokOpnamePersediaan::find($row['StokOpnameID']);
            if (!$stokOpname) {
                throw ValidationException::withMessages([
                    "rows.$index.StokOpnameID" => 'Stok opname tidak ditemukan.',
                ]);
            }

            if ((int) $stokOpname->PersediaanID !== (int) $persediaan->PersediaanID){
                throw ValidationException::withMessages([
                    "rows.$index.StokOpnameID" => 'Stok opname bukan milik Persediaan ini.',
                ]);
            }
        }

        $savedItems = DB::transaction(function () use ($validated, $persediaan){
            $result = [];
            foreach ($validated['rows'] as $row) {
                if(isset($row['TestPricingID']) && $row['TestPricingID'] !== null){
                    $item = TestPricingPersediaan::findOrFail($row['TestPricingID']);
                    $item->update($this->calculate($row));
                }
                else{
                    $item = TestPricingPersediaan::create([
                        'PersediaanID' =>
                            $persediaan->PersediaanID,
                        'StokOpnameID' =>
                            $row['StokOpnameID'],
                        ...$this->calculate($row),
                    ]);
                }
                $result[] = $item
                    ->fresh()
                    ->load('stokOpname');
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data test pricing berhasil disimpan.',
            'data' => collect($savedItems)
                ->map(fn (TestPricingPersediaan $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function destroy(Request $request, TestPricingPersediaan $test_pricing_persediaan): JsonResponse {
        $this->resolveAuthorizedStokOpname(
            $request,
            (int) $test_pricing_persediaan->StokOpnameID,
            (int) $test_pricing_persediaan->PersediaanID
        );
        $test_pricing_persediaan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data test pricing berhasil dihapus.',
        ]);
    }
}
