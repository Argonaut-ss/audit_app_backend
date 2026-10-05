<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\Kas\Kas;
use App\Models\Kas\ProsedurKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurKasController extends Controller
{
    private function prosedurCheck(int $kasId): void
    {
        $kas = Kas::find($kasId);

        if (! $kas) {
            return;
        }

        $kas->updateQuietly([
            'ProsedurCheck' =>
                $kas->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/kas/{kas}/prosedur
     */
    public function index(
        Kas $kas
    ): JsonResponse {
        $prosedurs = $kas->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-kas
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'kas_id' => [
                'required',
                'integer',
                'exists:kas,KasID',
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

        $prosedur = ProsedurKas::create([
            'kas_id' =>
                $validated['kas_id'],
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
            (int) $validated['kas_id']
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
     * Kesimpulan -> UPDATE Kas
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kas_id' => [
                'required',
                'integer',
                'exists:kas,KasID',
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
                'exists:prosedur_kas,id',
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

        $kasId =
            (int) $validated['kas_id'];

        $kas = Kas::findOrFail(
            $kasId
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
            ProsedurKas::whereIn(
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
                (int) $prosedur->kas_id
                !== $kasId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Kas ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $kas,
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
                            ProsedurKas::create([
                                'kas_id' =>
                                    $kas->KasID,
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
                    $kas->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $kas->KasID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $kas
                    ->fresh()
                    ->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-kas/{prosedurKas}
     */
    public function update(
        Request $request,
        ProsedurKas $prosedurKas
    ): JsonResponse {
        $validated = $request->validate([
            'kas_id' => [
                'required',
                'integer',
                'exists:kas,KasID',
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
            $prosedurKas->kas_id
            !== (int) $validated['kas_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified kas.',
            ], 422);
        }

        $prosedurKas->update([
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
            $prosedurKas->kas_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' =>
                $prosedurKas->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-kas/{prosedurKas}
     */
    public function destroy(
        ProsedurKas $prosedurKas
    ): JsonResponse {
        $kasId =
            $prosedurKas->kas_id;

        $prosedurKas->delete();

        $this->prosedurCheck($kasId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}