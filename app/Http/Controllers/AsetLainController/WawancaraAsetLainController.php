<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\WawancaraAsetLain;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WawancaraAsetLainController extends Controller
{
    private function resolveAuthorizedAsetLain(Request $request, int $asetLainId): AsetLain
    {
        $asetLain = AsetLain::findOrFail($asetLainId);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetLain->JwbKasusID)
            ->firstOrFail();

        return $asetLain;
    }

    private function updateWawancaraCheck(AsetLain $asetLain): void
    {
        $asetLain->updateQuietly([
            'WawancaraCheck' => WawancaraAsetLain::where(
                'AsetLainID',
                $asetLain->AsetLainID
            )->exists(),
        ]);
    }

    private function serialize(WawancaraAsetLain $wawancara): array
    {
        return [
            'WawancaraID' => $wawancara->WawancaraID,
            'AsetLainID' => $wawancara->AsetLainID,
            'Pertanyaan' => $wawancara->Pertanyaan,
            'Jawaban' => $wawancara->Jawaban,
            'created_at' => $wawancara->created_at,
            'updated_at' => $wawancara->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
        ]);
        $asetLain = $this->resolveAuthorizedAsetLain(
            $request,
            (int) $validated['AsetLainID']
        );

        $items = WawancaraAsetLain::where('AsetLainID', $asetLain->AsetLainID)
            ->orderBy('WawancaraID')
            ->get()
            ->map(fn (WawancaraAsetLain $item) => $this->serialize($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
            'Pertanyaan' => ['required', 'string'],
            'Jawaban' => ['nullable', 'string'],
        ]);
        $asetLain = $this->resolveAuthorizedAsetLain(
            $request,
            (int) $validated['AsetLainID']
        );

        $item = WawancaraAsetLain::create([
            'AsetLainID' => $asetLain->AsetLainID,
            'Pertanyaan' => $validated['Pertanyaan'],
            'Jawaban' => $validated['Jawaban'] ?? null,
        ]);
        $this->updateWawancaraCheck($asetLain);

        return response()->json([
            'success' => true,
            'message' => 'Data wawancara berhasil disimpan.',
            'data' => $this->serialize($item),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = WawancaraAsetLain::findOrFail($id);
        $this->resolveAuthorizedAsetLain($request, (int) $item->AsetLainID);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($item),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = WawancaraAsetLain::findOrFail($id);
        $this->resolveAuthorizedAsetLain($request, (int) $item->AsetLainID);

        $validated = $request->validate([
            'Pertanyaan' => ['sometimes', 'string'],
            'Jawaban' => ['sometimes', 'nullable', 'string'],
        ]);
        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data wawancara berhasil diperbarui.',
            'data' => $this->serialize($item->fresh()),
        ]);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.WawancaraID' => [
                'nullable',
                'integer',
                'exists:wawancara_aset_lain,WawancaraID',
                'distinct',
            ],
            'rows.*.Pertanyaan' => ['required', 'string'],
            'rows.*.Jawaban' => ['nullable', 'string'],
        ]);
        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $validated['AsetLainID']);

        $ids = collect($validated['rows'])
            ->pluck('WawancaraID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $existing = WawancaraAsetLain::where('AsetLainID', $asetLain->AsetLainID)
            ->whereIn('WawancaraID', $ids)
            ->get()
            ->keyBy('WawancaraID');

        foreach ($ids as $id) {
            if (! $existing->has($id)) {
                throw ValidationException::withMessages([
                    'rows' => "Wawancara ID {$id} bukan milik Aset Lain ini.",
                ]);
            }
        }

        $saved = DB::transaction(function () use ($validated, $existing, $asetLain) {
            $result = [];
            foreach ($validated['rows'] as $row) {
                if (! empty($row['WawancaraID'])) {
                    $item = $existing->get((int) $row['WawancaraID']);
                    $item->update([
                        'Pertanyaan' => $row['Pertanyaan'],
                        'Jawaban' => $row['Jawaban'] ?? null,
                    ]);
                } else {
                    $item = WawancaraAsetLain::create([
                        'AsetLainID' => $asetLain->AsetLainID,
                        'Pertanyaan' => $row['Pertanyaan'],
                        'Jawaban' => $row['Jawaban'] ?? null,
                    ]);
                }

                $result[] = $item->fresh();
            }

            $this->updateWawancaraCheck($asetLain);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data wawancara berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (WawancaraAsetLain $item) => $this->serialize($item))
                ->values(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = WawancaraAsetLain::findOrFail($id);
        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $item->AsetLainID);
        $item->delete();
        $this->updateWawancaraCheck($asetLain);

        return response()->json([
            'success' => true,
            'message' => 'Data wawancara berhasil dihapus.',
        ]);
    }
}
