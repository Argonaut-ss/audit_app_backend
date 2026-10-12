<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\ProsedurAsetLain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurAsetLainController extends Controller
{
    private function prosedurCheck(int $asetLainId): void
    {
        $asetLain = AsetLain::find($asetLainId);

        if (! $asetLain) {
            return;
        }

        $asetLain->updateQuietly([
            'ProsedurCheck' => $asetLain->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/aset-lain/{asetLain}/prosedur
     */
    public function index(AsetLain $asetLain): JsonResponse
    {
        $prosedurs = $asetLain->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-aset-lain
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'aset_lain_id' => [
                'required',
                'integer',
                'exists:aset_lain,AsetLainID',
            ],
            'nama_prosedur' => [
                'required',
                'string',
            ],
            'index' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tanggal' => [
                'required',
                'date',
            ],
            'checkbox' => [
                'required',
                'boolean',
            ],
        ]);

        $prosedur = ProsedurAsetLain::create([
            'aset_lain_id' => $validated['aset_lain_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $validated['aset_lain_id']
        );

        return response()->json([
            'message' => 'Prosedur created successfully.',
            'data' => $prosedur,
        ], 201);
    }

    /**
     * Bulk save procedure entries.
     *
     * id null   -> CREATE
     * id exists -> UPDATE
     * Kesimpulan -> UPDATE AsetLain
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aset_lain_id' => [
                'required',
                'integer',
                'exists:aset_lain,AsetLainID',
            ],
            'Kesimpulan' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'prosedurs' => [
                'required',
                'array',
                'min:1',
            ],
            'prosedurs.*.id' => [
                'nullable',
                'integer',
                'exists:prosedur_aset_lain,id',
            ],
            'prosedurs.*.nama_prosedur' => [
                'required',
                'string',
            ],
            'prosedurs.*.index' => [
                'nullable',
                'string',
                'max:255',
            ],
            'prosedurs.*.tanggal' => [
                'required',
                'date',
            ],
            'prosedurs.*.checkbox' => [
                'required',
                'boolean',
            ],
        ]);

        $asetLainId = (int) $validated['aset_lain_id'];

        $asetLain = AsetLain::findOrFail($asetLainId);

        $procedureIds = collect($validated['prosedurs'])
            ->pluck('id')
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingProsedurs = ProsedurAsetLain::whereIn(
            'id',
            $procedureIds
        )
            ->get()
            ->keyBy('id');

        foreach ($procedureIds as $procedureId) {
            $prosedur = $existingProsedurs->get($procedureId);

            if (! $prosedur) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} tidak ditemukan.",
                ], 422);
            }

            if ((int) $prosedur->aset_lain_id !== $asetLainId) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Aset Lain ini.",
                ], 422);
            }
        }

        $savedProsedurs = DB::transaction(function () use (
            $validated,
            $asetLain,
            $existingProsedurs
        ) {
            $saved = [];

            foreach ($validated['prosedurs'] as $row) {
                if (!is_null($row['id'] ?? null)) {
                    $prosedur = $existingProsedurs->get(
                        (int) $row['id']
                    );

                    $prosedur->update([
                        'nama_prosedur' => $row['nama_prosedur'],
                        'index' => $row['index'] ?? null,
                        'tanggal' => $row['tanggal'],
                        'checkbox' => $row['checkbox'],
                    ]);
                } else {
                    $prosedur = ProsedurAsetLain::create([
                        'aset_lain_id' => $asetLain->AsetLainID,
                        'nama_prosedur' => $row['nama_prosedur'],
                        'index' => $row['index'] ?? null,
                        'tanggal' => $row['tanggal'],
                        'checkbox' => $row['checkbox'],
                    ]);
                }

                $saved[] = $prosedur->fresh();
            }

            if (array_key_exists('Kesimpulan', $validated)) {
                $asetLain->update([
                    'Kesimpulan' => $validated['Kesimpulan'],
                ]);
            }

            $this->prosedurCheck(
                (int) $asetLain->AsetLainID
            );

            return $saved;
        });

        return response()->json([
            'message' => 'Semua data prosedur berhasil disimpan.',
            'data' => $savedProsedurs,
            'Kesimpulan' => $asetLain->fresh()->Kesimpulan,
        ]);
    }

    /**
     * PUT/PATCH /api/prosedur-aset-lain/{prosedurAsetLain}
     */
    public function update(
        Request $request,
        ProsedurAsetLain $prosedurAsetLain
    ): JsonResponse {
        $validated = $request->validate([
            'aset_lain_id' => [
                'required',
                'integer',
                'exists:aset_lain,AsetLainID',
            ],
            'nama_prosedur' => [
                'required',
                'string',
            ],
            'index' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tanggal' => [
                'required',
                'date',
            ],
            'checkbox' => [
                'required',
                'boolean',
            ],
        ]);

        if (
            (int) $prosedurAsetLain->aset_lain_id
            !== (int) $validated['aset_lain_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified aset lain.',
            ], 422);
        }

        $prosedurAsetLain->update([
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $prosedurAsetLain->aset_lain_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' => $prosedurAsetLain->fresh(),
        ]);
    }

    /**
     * DELETE /api/prosedur-aset-lain/{prosedurAsetLain}
     */
    public function destroy(
        ProsedurAsetLain $prosedurAsetLain
    ): JsonResponse {
        $asetLainId = (int) $prosedurAsetLain->aset_lain_id;

        $prosedurAsetLain->delete();

        $this->prosedurCheck($asetLainId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ]);
    }
}