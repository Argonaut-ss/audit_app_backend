<?php

namespace App\Http\Controllers\BebanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\BebanUsaha\ProsedurBebanUsaha;
use App\Models\BebanUsaha\BebanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurBebanUsahaController extends Controller
{
    private function prosedurCheck(int $bebanUsahaId): void
    {
        $bebanUsaha = BebanUsaha::find($bebanUsahaId);

        if (! $bebanUsaha) {
            return;
        }

        $bebanUsaha->updateQuietly([
            'ProsedurCheck' =>
                $bebanUsaha->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/beban-usahas/{bebanUsaha}/prosedur
     */
    public function index(
        BebanUsaha $bebanUsaha
    ): JsonResponse {
        $prosedurs = $bebanUsaha->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-beban-usaha
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'beban_usaha_id' => [
                'required',
                'integer',
                'exists:beban_usaha,BebanUsahaID',
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

        $prosedur = ProsedurBebanUsaha::create([
            'beban_usaha_id' =>
                $validated['beban_usaha_id'],
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
            (int) $validated['beban_usaha_id']
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
     * Kesimpulan -> UPDATE BebanUsaha
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'beban_usaha_id' => [
                'required',
                'integer',
                'exists:beban_usaha,BebanUsahaID',
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
                'exists:prosedur_beban_usaha,id',
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

        $bebanUsahaId =
            (int) $validated['beban_usaha_id'];

        $bebanUsaha = BebanUsaha::findOrFail(
            $bebanUsahaId
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
            ProsedurBebanUsaha::whereIn(
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
                (int) $prosedur->beban_usaha_id
                !== $bebanUsahaId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Beban Usaha ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $bebanUsaha,
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
                            ProsedurBebanUsaha::create([
                                'beban_usaha_id' =>
                                    $bebanUsaha
                                        ->BebanUsahaID,
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
                    $bebanUsaha->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $bebanUsaha->BebanUsahaID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $bebanUsaha
                    ->fresh()
                    ->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-beban-usaha/{prosedurBebanUsaha}
     */
    public function update(
        Request $request,
        ProsedurBebanUsaha $prosedurBebanUsaha
    ): JsonResponse {
        $validated = $request->validate([
            'beban_usaha_id' => [
                'required',
                'integer',
                'exists:beban_usaha,BebanUsahaID',
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
            $prosedurBebanUsaha
                ->beban_usaha_id
            !== (int) $validated['beban_usaha_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified beban usaha.',
            ], 422);
        }

        $prosedurBebanUsaha->update([
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
            $prosedurBebanUsaha->beban_usaha_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' =>
                $prosedurBebanUsaha->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-beban-usaha/{prosedurBebanUsaha}
     */
    public function destroy(
        ProsedurBebanUsaha $prosedurBebanUsaha
    ): JsonResponse {
        $bebanUsahaId =
            $prosedurBebanUsaha->beban_usaha_id;

        $prosedurBebanUsaha->delete();

        $this->prosedurCheck($bebanUsahaId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}