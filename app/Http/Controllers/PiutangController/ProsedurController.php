<?php

namespace App\Http\Controllers\PiutangController;

use App\Http\Controllers\Controller;
use App\Models\Piutang\Piutang;
use App\Models\Piutang\Prosedur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurController extends Controller
{
    private function prosedurCheck(int $piutangId): void
    {
        $piutang = Piutang::find($piutangId);

        if (! $piutang) {
            return;
        }

        $piutang->updateQuietly([
            'ProsedurCheck' => $piutang->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/piutangs/{piutang}/prosedur
     *
     * List all procedure entries belonging to a piutang.
     */
    public function index(Piutang $piutang): JsonResponse
    {
        $prosedurs = $piutang->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur
     *
     * Create a single procedure entry OR bulk save
     * procedure entries + conclusion.
     */
    public function store(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | BULK SAVE
        |--------------------------------------------------------------------------
        |
        | Kalau request memiliki field "prosedurs",
        | maka proses sebagai bulk save.
        |
        */

        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        /*
        |--------------------------------------------------------------------------
        | SINGLE CREATE
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'piutang_id' => [
                'required',
                'integer',
                'exists:Piutang,PiutangID',
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

        $prosedur = Prosedur::create([
            'piutang_id' => $validated['piutang_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $validated['piutang_id']
        );

        return response()->json([
            'message' => 'Prosedur created successfully.',
            'data' => $prosedur,
        ], 201);
    }

    /**
     * Bulk save procedure entries.
     *
     * id null:
     * -> CREATE
     *
     * id exists:
     * -> UPDATE
     *
     * Kesimpulan:
     * -> UPDATE Piutang
     *
     * Semua proses berada dalam satu transaction.
     */
    private function bulkStore(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'piutang_id' => [
                'required',
                'integer',
                'exists:Piutang,PiutangID',
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
                'exists:prosedurs,id',
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

        $piutangId = (int) $validated['piutang_id'];

        $piutang = Piutang::findOrFail(
            $piutangId
        );

        /*
        |--------------------------------------------------------------------------
        | 2. CEK SEMUA ID YANG AKAN DI-UPDATE
        |--------------------------------------------------------------------------
        |
        | Semua prosedur yang dikirim harus benar-benar
        | milik Piutang yang sedang diproses.
        |
        */

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

        $existingProcedurs = Prosedur::whereIn(
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
                (int) $prosedur->piutang_id
                !== $piutangId
            ) {
                return response()->json([
                    'message' =>
                        "Prosedur ID {$procedureId} bukan milik Piutang ini.",
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. TRANSACTION
        |--------------------------------------------------------------------------
        */

        $savedProcedurs = DB::transaction(
            function () use (
                $validated,
                $piutang,
                $existingProcedurs
            ) {
                $saved = [];

                foreach (
                    $validated['prosedurs']
                    as $row
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE
                    |--------------------------------------------------------------------------
                    */

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
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE
                    |--------------------------------------------------------------------------
                    */

                    else {
                        $prosedur =
                            Prosedur::create([
                                'piutang_id' =>
                                    $piutang->PiutangID,

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

                /*
                |--------------------------------------------------------------------------
                | UPDATE KESIMPULAN
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'Kesimpulan',
                        $validated
                    )
                ) {
                    $piutang->update([
                        'Kesimpulan' =>
                            $validated['Kesimpulan'],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE CHECK
                |--------------------------------------------------------------------------
                */

                $this->prosedurCheck(
                    (int) $piutang->PiutangID
                );

                return $saved;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 4. RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' =>
                'Semua data prosedur berhasil disimpan.',

            'data' => $savedProcedurs,

            'Kesimpulan' =>
                $piutang->fresh()->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur/{prosedur}
     *
     * Update an existing procedure entry.
     */
    public function update(
        Request $request,
        Prosedur $prosedur
    ): JsonResponse {
        $validated = $request->validate([
            'piutang_id' => ['required', 'integer', 'exists:Piutang,PiutangID'],
            'nama_prosedur' => ['required', 'string'],
            'index' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'checkbox' => ['required', 'boolean'],
        ]);

        // Prevent editing an entry under a different piutang.
        if ($prosedur->piutang_id !== (int) $validated['piutang_id']) {
            return response()->json([
                'message' => 'The procedure does not belong to the specified piutang.',
            ], 422);
        }

        $prosedur->update([
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck($prosedur->piutang_id);

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' => $prosedur->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur/{prosedur}
     *
     * Delete a procedure entry by ID.
     */
    public function destroy(Prosedur $prosedur): JsonResponse
    {
        $piutangId = $prosedur->piutang_id;
        $prosedur->delete();

        $this->prosedurCheck($piutangId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}