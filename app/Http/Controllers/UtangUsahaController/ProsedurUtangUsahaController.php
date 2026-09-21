<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\UtangUsaha\ProsedurUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurUtangUsahaController extends Controller
{
    private function prosedurCheck(int $utangUsahaId): void
    {
        $utangUsaha = UtangUsaha::find($utangUsahaId);

        if (! $utangUsaha) {
            return;
        }

        $utangUsaha->updateQuietly([
            'ProsedurCheck' => $utangUsaha->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/utang-usahas/{utangUsaha}/prosedur
     */
    public function index(UtangUsaha $utangUsaha): JsonResponse
    {
        $prosedurs = $utangUsaha->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-utang-usaha
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'utang_usaha_id' => [
                'required',
                'integer',
                'exists:utang_usaha,UtangUsahaID',
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

        $prosedur = ProsedurUtangUsaha::create([
            'utang_usaha_id' => $validated['utang_usaha_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $validated['utang_usaha_id']
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
     * Kesimpulan -> UPDATE UtangUsaha
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'utang_usaha_id' => [
                'required',
                'integer',
                'exists:utang_usaha,UtangUsahaID',
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
                'exists:prosedur_utang_usaha,id',
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

        $utangUsahaId = (int) $validated['utang_usaha_id'];

        $utangUsaha = UtangUsaha::findOrFail(
            $utangUsahaId
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

        $existingProcedurs = ProsedurUtangUsaha::whereIn(
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
                (int) $prosedur->utang_usaha_id
                !== $utangUsahaId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Utang Usaha ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $utangUsaha,
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
                            ProsedurUtangUsaha::create([
                                'utang_usaha_id' =>
                                    $utangUsaha->UtangUsahaID,
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
                    $utangUsaha->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $utangUsaha->UtangUsahaID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $utangUsaha->fresh()->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-utang-usaha/{prosedurUtangUsaha}
     */
    public function update(
        Request $request,
        ProsedurUtangUsaha $prosedurUtangUsaha
    ): JsonResponse {
        $validated = $request->validate([
            'utang_usaha_id' => ['required', 'integer', 'exists:utang_usaha,UtangUsahaID'],
            'nama_prosedur' => ['required', 'string'],
            'index' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'checkbox' => ['required', 'boolean'],
        ]);

        if ($prosedurUtangUsaha->utang_usaha_id !== (int) $validated['utang_usaha_id']) {
            return response()->json([
                'message' => 'The procedure does not belong to the specified utang usaha.',
            ], 422);
        }

        $prosedurUtangUsaha->update([
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck($prosedurUtangUsaha->utang_usaha_id);

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' => $prosedurUtangUsaha->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-utang-usaha/{prosedurUtangUsaha}
     */
    public function destroy(ProsedurUtangUsaha $prosedurUtangUsaha): JsonResponse
    {
        $utangUsahaId = $prosedurUtangUsaha->utang_usaha_id;
        $prosedurUtangUsaha->delete();

        $this->prosedurCheck($utangUsahaId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}
