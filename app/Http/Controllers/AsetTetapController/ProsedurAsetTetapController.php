<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\AsetTetap\AsetTetap;
use App\Models\AsetTetap\ProsedurAsetTetap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurAsetTetapController extends Controller
{
    private function prosedurCheck(int $asetTetapId): void
    {
        $asetTetap = AsetTetap::find($asetTetapId);

        if (! $asetTetap) {
            return;
        }

        $asetTetap->updateQuietly([
            'ProsedurCheck' =>
                $asetTetap->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/aset-tetaps/{asetTetap}/prosedur
     */
    public function index(
        AsetTetap $asetTetap
    ): JsonResponse {
        $prosedurs = $asetTetap->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-aset-tetap
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'aset_tetap_id' => [
                'required',
                'integer',
                'exists:aset_tetap,AsetTetapID',
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

        $prosedur = ProsedurAsetTetap::create([
            'aset_tetap_id' =>
                $validated['aset_tetap_id'],
            'nama_prosedur' =>
                $validated['nama_prosedur'],
            'index' =>
                $validated['index'] ?? null,
            'tanggal' =>
                $validated['tanggal'],
            'checkbox' =>
                $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $validated['aset_tetap_id']
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
     * Kesimpulan -> UPDATE AsetTetap
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aset_tetap_id' => [
                'required',
                'integer',
                'exists:aset_tetap,AsetTetapID',
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
                'exists:prosedur_aset_tetap,id',
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

        $asetTetapId =
            (int) $validated['aset_tetap_id'];

        $asetTetap = AsetTetap::findOrFail(
            $asetTetapId
        );

        $procedureIds = collect(
            $validated['prosedurs']
        )
            ->pluck('id')
            ->filter(
                fn ($id) =>
                    !is_null($id)
                    && $id !== ''
            )
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->unique()
            ->values();

        $existingProcedurs =
            ProsedurAsetTetap::whereIn(
                'id',
                $procedureIds
            )
                ->get()
                ->keyBy('id');

        foreach ($procedureIds as $procedureId) {
            $prosedur = $existingProcedurs->get(
                $procedureId
            );

            if (!$prosedur) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} tidak ditemukan.",
                ], 422);
            }

            if (
                (int) $prosedur->aset_tetap_id
                !== $asetTetapId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Aset Tetap ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $asetTetap,
                $existingProcedurs
            ) {
                $saved = [];

                foreach (
                    $validated['prosedurs']
                    as $row
                ) {
                    if (
                        !is_null($row['id'] ?? null)
                    ) {
                        $prosedur =
                            $existingProcedurs->get(
                                (int) $row['id']
                            );

                        $prosedur->update([
                            'nama_prosedur' =>
                                $row['nama_prosedur'],
                            'index' =>
                                $row['index'] ?? null,
                            'tanggal' =>
                                $row['tanggal'],
                            'checkbox' =>
                                $row['checkbox'],
                        ]);
                    } else {
                        $prosedur =
                            ProsedurAsetTetap::create([
                                'aset_tetap_id' =>
                                    $asetTetap
                                        ->AsetTetapID,
                                'nama_prosedur' =>
                                    $row['nama_prosedur'],
                                'index' =>
                                    $row['index'] ?? null,
                                'tanggal' =>
                                    $row['tanggal'],
                                'checkbox' =>
                                    $row['checkbox'],
                            ]);
                    }

                    $saved[] =
                        $prosedur->fresh();
                }

                if (
                    array_key_exists(
                        'Kesimpulan',
                        $validated
                    )
                ) {
                    $asetTetap->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $asetTetap->AsetTetapID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $asetTetap
                    ->fresh()
                    ->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-aset-tetap/{prosedurAsetTetap}
     */
    public function update(
        Request $request,
        ProsedurAsetTetap $prosedurAsetTetap
    ): JsonResponse {
        $validated = $request->validate([
            'aset_tetap_id' => [
                'required',
                'integer',
                'exists:aset_tetap,AsetTetapID',
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
            $prosedurAsetTetap
                ->aset_tetap_id
            !== (int) $validated['aset_tetap_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified aset tetap.',
            ], 422);
        }

        $prosedurAsetTetap->update([
            'nama_prosedur' =>
                $validated['nama_prosedur'],
            'index' =>
                $validated['index'] ?? null,
            'tanggal' =>
                $validated['tanggal'],
            'checkbox' =>
                $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            $prosedurAsetTetap->aset_tetap_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' =>
                $prosedurAsetTetap->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-aset-tetap/{prosedurAsetTetap}
     */
    public function destroy(
        ProsedurAsetTetap $prosedurAsetTetap
    ): JsonResponse {
        $asetTetapId =
            $prosedurAsetTetap->aset_tetap_id;

        $prosedurAsetTetap->delete();

        $this->prosedurCheck($asetTetapId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}