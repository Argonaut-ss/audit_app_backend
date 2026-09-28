<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\PendapatanUsaha\ProsedurPendapatanUsaha;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurPendapatanUsahaController extends Controller
{
    private function prosedurCheck(int $pendapatanUsahaId): void
    {
        $pendapatanUsaha = PendapatanUsaha::find(
            $pendapatanUsahaId
        );

        if (! $pendapatanUsaha) {
            return;
        }

        $pendapatanUsaha->updateQuietly([
            'ProsedurCheck' =>
                $pendapatanUsaha->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/pendapatan-usahas/{pendapatanUsaha}/prosedur
     */
    public function index(
        PendapatanUsaha $pendapatanUsaha
    ): JsonResponse {
        $prosedurs = $pendapatanUsaha->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-pendapatan-usaha
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'pendapatan_usaha_id' => [
                'required',
                'integer',
                'exists:pendapatan_usaha,PendapatanUsahaID',
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

        $prosedur = ProsedurPendapatanUsaha::create([
            'pendapatan_usaha_id' =>
                $validated['pendapatan_usaha_id'],
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
            (int) $validated['pendapatan_usaha_id']
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
     * Kesimpulan -> UPDATE PendapatanUsaha
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pendapatan_usaha_id' => [
                'required',
                'integer',
                'exists:pendapatan_usaha,PendapatanUsahaID',
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
                'exists:prosedur_pendapatan_usaha,id',
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

        $pendapatanUsahaId =
            (int) $validated['pendapatan_usaha_id'];

        $pendapatanUsaha = PendapatanUsaha::findOrFail(
            $pendapatanUsahaId
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
            ProsedurPendapatanUsaha::whereIn(
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
                (int) $prosedur->pendapatan_usaha_id
                !== $pendapatanUsahaId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Pendapatan Usaha ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $pendapatanUsaha,
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
                            ProsedurPendapatanUsaha::create([
                                'pendapatan_usaha_id' =>
                                    $pendapatanUsaha
                                        ->PendapatanUsahaID,
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
                    $pendapatanUsaha->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                $this->prosedurCheck(
                    (int) $pendapatanUsaha
                        ->PendapatanUsahaID
                );

                return $saved;
            }
        );

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' =>
                $pendapatanUsaha
                    ->fresh()
                    ->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-pendapatan-usaha/{prosedurPendapatanUsaha}
     */
    public function update(
        Request $request,
        ProsedurPendapatanUsaha $prosedurPendapatanUsaha
    ): JsonResponse {
        $validated = $request->validate([
            'pendapatan_usaha_id' => [
                'required',
                'integer',
                'exists:pendapatan_usaha,PendapatanUsahaID',
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
            $prosedurPendapatanUsaha
                ->pendapatan_usaha_id
            !== (int) $validated['pendapatan_usaha_id']
        ) {
            return response()->json([
                'message' =>
                    'The procedure does not belong to the specified pendapatan usaha.',
            ], 422);
        }

        $prosedurPendapatanUsaha->update([
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
            $prosedurPendapatanUsaha
                ->pendapatan_usaha_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' =>
                $prosedurPendapatanUsaha->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-pendapatan-usaha/{prosedurPendapatanUsaha}
     */
    public function destroy(
        ProsedurPendapatanUsaha $prosedurPendapatanUsaha
    ): JsonResponse {
        $pendapatanUsahaId =
            $prosedurPendapatanUsaha
                ->pendapatan_usaha_id;

        $prosedurPendapatanUsaha->delete();

        $this->prosedurCheck($pendapatanUsahaId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}