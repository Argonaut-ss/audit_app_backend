<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\SetaraKas\SetaraKas;
use App\Models\SetaraKas\ProsedurSetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsedurSetaraKasController extends Controller
{
    private function prosedurCheck(int $setaraKasId): void
    {
        $setaraKas = SetaraKas::find($setaraKasId);

        if (! $setaraKas) {
            return;
        }

        $setaraKas->updateQuietly([
            'ProsedurCheck' => $setaraKas->prosedurs()->exists(),
        ]);
    }

    /**
     * GET /api/setara-kas/{setaraKas}/prosedur
     */
    public function index(SetaraKas $setaraKas): JsonResponse
    {
        $prosedurs = $setaraKas->prosedurs()
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Prosedur retrieved successfully.',
            'data' => $prosedurs,
        ]);
    }

    /**
     * POST /api/prosedur-setara-kas
     *
     * Single create OR bulk save (entries + conclusion).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('prosedurs')) {
            return $this->bulkStore($request);
        }

        $validated = $request->validate([
            'setara_kas_id' => [
                'required',
                'integer',
                'exists:setara_kas,SetaraKasID',
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

        $prosedur = ProsedurSetaraKas::create([
            'setara_kas_id' => $validated['setara_kas_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck((int) $validated['setara_kas_id']);

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
     * Kesimpulan -> UPDATE SetaraKas
     */
    private function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setara_kas_id' => [
                'required',
                'integer',
                'exists:setara_kas,SetaraKasID',
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
                'exists:prosedur_setara_kas,id',
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

        $setaraKasId = (int) $validated['setara_kas_id'];

        $setaraKas = SetaraKas::findOrFail($setaraKasId);

        $procedureIds = collect($validated['prosedurs'])
            ->pluck('id')
            ->filter(fn ($id) => ! is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingProcedurs = ProsedurSetaraKas::whereIn(
            'id',
            $procedureIds
        )->get()->keyBy('id');

        foreach ($procedureIds as $procedureId) {
            $prosedur = $existingProcedurs->get($procedureId);

            if (! $prosedur) {
                return response()->json([
                    'message' => "Prosedur ID {$procedureId} tidak ditemukan.",
                ], 422);
            }

            if ((int) $prosedur->setara_kas_id !== $setaraKasId) {
                return response()->json([
                    'message' => "Prosedur ID {$procedureId} bukan milik Setara Kas ini.",
                ], 422);
            }
        }

        $savedProcedurs = DB::transaction(function () use (
            $validated,
            $setaraKas,
            $existingProcedurs
        ) {
            $saved = [];

            foreach ($validated['prosedurs'] as $row) {
                if (! is_null($row['id'] ?? null)) {
                    $prosedur = $existingProcedurs->get(
                        (int) $row['id']
                    );

                    $prosedur->update([
                        'nama_prosedur' => $row['nama_prosedur'],
                        'index' => $row['index'] ?? null,
                        'tanggal' => $row['tanggal'],
                        'checkbox' => $row['checkbox'],
                    ]);
                } else {
                    $prosedur = ProsedurSetaraKas::create([
                        'setara_kas_id' => $setaraKas->SetaraKasID,
                        'nama_prosedur' => $row['nama_prosedur'],
                        'index' => $row['index'] ?? null,
                        'tanggal' => $row['tanggal'],
                        'checkbox' => $row['checkbox'],
                    ]);
                }

                $saved[] = $prosedur->fresh();
            }

            if (array_key_exists('Kesimpulan', $validated)) {
                $setaraKas->update([
                    'Kesimpulan' => $validated['Kesimpulan'],
                ]);
            }

            $this->prosedurCheck((int) $setaraKas->SetaraKasID);

            return $saved;
        });

        return response()->json([
            'message' => 'Semua data prosedur berhasil disimpan.',
            'data' => $savedProcedurs,
            'Kesimpulan' => $setaraKas->fresh()->Kesimpulan,
        ], 200);
    }

    /**
     * PUT/PATCH /api/prosedur-setara-kas/{prosedurSetaraKas}
     */
    public function update(
        Request $request,
        ProsedurSetaraKas $prosedurSetaraKas
    ): JsonResponse {
        $validated = $request->validate([
            'setara_kas_id' => [
                'required',
                'integer',
                'exists:setara_kas,SetaraKasID',
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
            (int) $prosedurSetaraKas->setara_kas_id
            !== (int) $validated['setara_kas_id']
        ) {
            return response()->json([
                'message' => 'The procedure does not belong to the specified setara kas.',
            ], 422);
        }

        $prosedurSetaraKas->update([
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        $this->prosedurCheck(
            (int) $prosedurSetaraKas->setara_kas_id
        );

        return response()->json([
            'message' => 'Prosedur updated successfully.',
            'data' => $prosedurSetaraKas->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/prosedur-setara-kas/{prosedurSetaraKas}
     */
    public function destroy(
        ProsedurSetaraKas $prosedurSetaraKas
    ): JsonResponse {
        $setaraKasId = (int) $prosedurSetaraKas->setara_kas_id;

        $prosedurSetaraKas->delete();

        $this->prosedurCheck($setaraKasId);

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}