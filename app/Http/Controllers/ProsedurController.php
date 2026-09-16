<?php

namespace App\Http\Controllers;

use App\Models\Piutang;
use App\Models\Prosedur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProsedurController extends Controller
{
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
     * Create a new procedure entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'piutang_id' => ['required', 'integer', 'exists:Piutang,PiutangID'],
            'nama_prosedur' => ['required', 'string'],
            'index' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'checkbox' => ['required', 'boolean'],
        ]);

        $prosedur = Prosedur::create([
            'piutang_id' => $validated['piutang_id'],
            'nama_prosedur' => $validated['nama_prosedur'],
            'index' => $validated['index'] ?? null,
            'tanggal' => $validated['tanggal'],
            'checkbox' => $validated['checkbox'],
        ]);

        return response()->json([
            'message' => 'Prosedur created successfully.',
            'data' => $prosedur,
        ], 201);
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
        $prosedur->delete();

        return response()->json([
            'message' => 'Prosedur deleted successfully.',
        ], 200);
    }
}