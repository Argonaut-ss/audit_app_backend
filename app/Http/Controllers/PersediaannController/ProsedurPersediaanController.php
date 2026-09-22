<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\Persediaan\ProsedurPersediaan;
use App\Models\Persediaan\Persediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurPersediaanController extends Controller
{
    private function prosedurCheck(int $persediaanId): void
    {
        $persediaan = Persediaan::find($persediaanId);

        if (! $persediaan) {
            return;
        }

        $persediaan->updateQuietly([
            'ProsedurCheck' => $persediaan->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/persediaans/{persediaan}/prosedur
     */
    public function index(Persediaan $persediaan): JsonResponse
    {
        $prosedurs = $persediaan->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-persediaan
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'persediaan_id' => [
                'required',
                'integer',
                'exists:persediaan,PersediaanID',
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

        $prosedur = ProsedurPersediaan::create([
            'persediaan_id' => $validated['persediaan_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $validated['persediaan_id']
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
     * Kesimpulan -> UPDATE Persediaan
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persediaan_id' => [
                'required',
                'integer',
                'exists:persediaan,PersediaanID',
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
                'exists:prosedur_persediaan,id',
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

        $persediaanId = (int) $validated['persediaan_id'];

        $persediaan = Persediaan::findOrFail(
            $persediaanId
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

        $existingProcedurs = ProsedurPersediaan::whereIn(
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
                    'message' => "Prosedur ID {$procedureId} tidak ditemukan.",
                ], 422);
            }

            if (
                (int) $prosedur->persediaan_id
                !== $persediaanId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Persediaan ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $persediaan,
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
                            ProsedurPersediaan::create([
                                'persediaan_id' =>
                                    $persediaan->PersediaanID,
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
                    $persediaan->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $persediaan->PersediaanID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $persediaan->fresh()->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-persediaan/{prosedurPersediaan}
     */
    public function update(
        Request $request,
        ProsedurPersediaan $prosedurPersediaan
    ): JsonResponse {
        $validated = $request->validate([
            'persediaan_id' => [
                'required',
                'integer',
                'exists:persediaan,PersediaanID',
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
            $prosedurPersediaan->persediaan_id
            !== (int) $validated['persediaan_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified persediaan.',
            ], 422);
        }

        $prosedurPersediaan->update([
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
            $prosedurPersediaan->persediaan_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' => $prosedurPersediaan->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-persediaan/{prosedurPersediaan}
     */
    public function destroy(
        ProsedurPersediaan $prosedurPersediaan
    ): JsonResponse {
        $persediaanId =
            $prosedurPersediaan->persediaan_id;

        $prosedurPersediaan->delete();

        $this->prosedurCheck($persediaanId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}