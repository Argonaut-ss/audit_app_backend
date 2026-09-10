<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\KonfirmasiPiutang;
use App\Models\Piutang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonfirmasiPiutangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KonfirmasiPiutang::with('piutang')
            ->whereHas('piutang', function ($query) use ($request) {
                $query->whereHas('JwbKasus', function ($query) use ($request) {
                    $query->forUser($request->user());
                });
            })
            ->orderByDesc('KonfirmasiPiutangID')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $konfirmasiPiutang = KonfirmasiPiutang::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiPiutang->piutang->JwbKasusID)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $konfirmasiPiutang,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => [
                'required',
                'integer',
                'exists:Piutang,PiutangID',
            ],
            'NamaCustomer' => [
                'required',
                'string',
                'max:255',
            ],
            'KotaCustomer' => [
                'required',
                'string',
                'max:255',
            ],
            'Jumlah' => [
                'required',
                'integer',
            ],
            'File' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ]);

        $piutang = Piutang::findOrFail($validated['PiutangID']);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        $item = new KonfirmasiPiutang;
        $item->PiutangID = $validated['PiutangID'];
        $item->NamaCustomer = $validated['NamaCustomer'];
        $item->KotaCustomer = $validated['KotaCustomer'];
        $item->Jumlah = $validated['Jumlah'];

        if ($request->hasFile('File')) {
            $item->File = file_get_contents(
                $request->file('File')->getRealPath()
            );
        }

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi piutang berhasil disimpan.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $konfirmasiPiutang = KonfirmasiPiutang::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiPiutang->piutang->JwbKasusID)
            ->firstOrFail();

        $validated = $request->validate([
            'NamaCustomer' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'KotaCustomer' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'Jumlah' => [
                'sometimes',
                'integer',
            ],
            'File' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ]);

        $konfirmasiPiutang->fill($validated);

        if ($request->hasFile('File')) {
            $konfirmasiPiutang->File = file_get_contents(
                $request->file('File')->getRealPath()
            );
        }

        $konfirmasiPiutang->save();

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi piutang berhasil diperbarui.',
            'data' => $konfirmasiPiutang->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $konfirmasiPiutang = KonfirmasiPiutang::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiPiutang->piutang->JwbKasusID)
            ->firstOrFail();

        $konfirmasiPiutang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi piutang berhasil dihapus.',
        ]);
    }
}