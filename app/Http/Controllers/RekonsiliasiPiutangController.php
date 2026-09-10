<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Piutang;
use App\Models\RekonsiliasiPiutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekonsiliasiPiutangController extends Controller
{
    public function index(Request $request, Piutang $piutang): JsonResponse
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        $items = $piutang->rekonsiliasiPiutang()->orderBy('TanggalFaktur')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request, Piutang $piutang): JsonResponse
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        $validated = $request->validate([
            'NamaCustomer' => ['nullable', 'string', 'max:255'],
            'NomorFaktur' => ['required', 'string', 'max:255'],
            'TanggalFaktur' => ['required', 'date'],
            'SaldoBuku' => ['required', 'numeric'],
            'SaldoCustomer' => ['required', 'numeric'],
            'Selisih' => ['required', 'numeric'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $item = $piutang->rekonsiliasiPiutang()->create([
            'PiutangID' => $piutang->PiutangID,
            'NamaCustomer' => $validated['NamaCustomer'] ?? null,
            'NomorFaktur' => $validated['NomorFaktur'],
            'TanggalFaktur' => $validated['TanggalFaktur'],
            'SaldoBuku' => $validated['SaldoBuku'],
            'SaldoCustomer' => $validated['SaldoCustomer'],
            'Selisih' => $validated['Selisih'],
            'Keterangan' => $validated['Keterangan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil disimpan.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, Piutang $piutang, RekonsiliasiPiutang $rekonsiliasiPiutang): JsonResponse
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        abort_unless($rekonsiliasiPiutang->PiutangID === $piutang->PiutangID, 404);

        $validated = $request->validate([
            'NamaCustomer' => ['nullable', 'string', 'max:255'],
            'NomorFaktur' => ['sometimes', 'string', 'max:255'],
            'TanggalFaktur' => ['sometimes', 'date'],
            'SaldoBuku' => ['sometimes', 'numeric'],
            'SaldoCustomer' => ['sometimes', 'numeric'],
            'Selisih' => ['sometimes', 'numeric'],
            'Keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $rekonsiliasiPiutang->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil diperbarui.',
            'data' => $rekonsiliasiPiutang->fresh(),
        ]);
    }

    public function destroy(Request $request, Piutang $piutang, RekonsiliasiPiutang $rekonsiliasiPiutang): JsonResponse
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        abort_unless($rekonsiliasiPiutang->PiutangID === $piutang->PiutangID, 404);

        $rekonsiliasiPiutang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi piutang berhasil dihapus.',
        ]);
    }
}
