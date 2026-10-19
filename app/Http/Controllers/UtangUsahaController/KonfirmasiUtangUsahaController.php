<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\KonfirmasiUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use App\Models\UtangUsaha\RekapBalasanUtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KonfirmasiUtangUsahaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KonfirmasiUtangUsaha::with('utangUsaha')
            ->whereHas('utangUsaha', function ($query) use ($request) {
                $query->whereHas('JwbKasus', function ($query) use ($request) {
                    $query->forUser($request->user());
                });
            })
            ->orderByDesc('KonfirmasiUtangUsahaID')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $konfirmasiUtangUsaha = KonfirmasiUtangUsaha::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiUtangUsaha->utangUsaha->JwbKasusID)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $konfirmasiUtangUsaha,
        ]);
    }

    public function file(Request $request, $id)
    {
        $konfirmasiUtangUsaha = KonfirmasiUtangUsaha::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiUtangUsaha->utangUsaha->JwbKasusID)
            ->firstOrFail();

        if (is_null($konfirmasiUtangUsaha->File)) {
            return response()->json([
                'success' => false,
                'message' => 'File konfirmasi utang usaha tidak ditemukan.',
            ], 404);
        }

        $filename = basename($konfirmasiUtangUsaha->NamaFile ?: 'konfirmasi-utang-usaha-file');
        $contentType = $konfirmasiUtangUsaha->TipeFile ?: 'application/octet-stream';

        $fileContent = $konfirmasiUtangUsaha->File;

        return response(
            $fileContent,
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' .addslashes($filename) . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UtangUsahaID' => [
                'required',
                'integer',
                'exists:utang_usaha,UtangUsahaID',
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

        $utangUsaha = UtangUsaha::findOrFail($validated['UtangUsahaID']);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $utangUsaha->JwbKasusID)
            ->firstOrFail();

        $item = new KonfirmasiUtangUsaha;
        $item->UtangUsahaID = $validated['UtangUsahaID'];
        $item->NamaCustomer = $validated['NamaCustomer'];
        $item->KotaCustomer = $validated['KotaCustomer'];
        $item->Jumlah = $validated['Jumlah'];

        if ($request->hasFile('File')) {
            $file = $request->file('File');
            $fileContent = file_get_contents($file->getRealPath());
            $item->File = $fileContent;
            $item->NamaFile = $file->getClientOriginalName();
            $item->TipeFile = $file->getMimeType();
        }

        $item->save();
        $this->KonfirmasiCheck($utangUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi utang usaha berhasil disimpan.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $konfirmasiUtangUsaha = KonfirmasiUtangUsaha::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiUtangUsaha->utangUsaha->JwbKasusID)
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

        $utangUsaha = $konfirmasiUtangUsaha->utangUsaha;

        DB::transaction(function () use ($request, $validated, $konfirmasiUtangUsaha, $utangUsaha) {
            $konfirmasiUtangUsaha->fill($validated);

            if ($request->hasFile('File')) {
                $file = $request->file('File');
                $konfirmasiUtangUsaha->File = file_get_contents($file->getRealPath());
                $konfirmasiUtangUsaha->NamaFile = $file->getClientOriginalName();
                $konfirmasiUtangUsaha->TipeFile = $file->getMimeType();
            }

            $konfirmasiUtangUsaha->save();

            $saldoBB = $konfirmasiUtangUsaha->Jumlah;
            $utangUsaha->rekapBalasan()
                ->where('KonfirmasiUtangUsahaID', $konfirmasiUtangUsaha->KonfirmasiUtangUsahaID)
                ->get()
                ->each(function (RekapBalasanUtangUsaha $rekap) use ($saldoBB) {
                    $rekap->update([
                        'SaldoBB' => $saldoBB,
                        'Selisih' => $saldoBB - ($rekap->SaldoJawab ?? 0),
                    ]);
                });

            $this->KonfirmasiCheck($utangUsaha);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi utang usaha berhasil diperbarui.',
            'data' => $konfirmasiUtangUsaha->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $konfirmasiUtangUsaha = KonfirmasiUtangUsaha::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiUtangUsaha->utangUsaha->JwbKasusID)
            ->firstOrFail();

        $utangUsaha = $konfirmasiUtangUsaha->utangUsaha;

        DB::transaction(function () use ($konfirmasiUtangUsaha, $utangUsaha) {
            $utangUsaha->rekapBalasan()
                ->where('KonfirmasiUtangUsahaID', $konfirmasiUtangUsaha->KonfirmasiUtangUsahaID)
                ->delete();
            $utangUsaha->rekonsiliasiUtangUsaha()
                ->where('KonfirmasiUtangUsahaID', $konfirmasiUtangUsaha->KonfirmasiUtangUsahaID)
                ->delete();
            $utangUsaha->prosedurAlternatif()
                ->where('KonfirmasiUtangUsahaID', $konfirmasiUtangUsaha->KonfirmasiUtangUsahaID)
                ->delete();

            $konfirmasiUtangUsaha->delete();

            $utangUsaha->updateQuietly([
                'KonfirmasiCheck' => $utangUsaha->konfirmasiUtangUsaha()->exists(),
                'RekapCheck' => $utangUsaha->rekapBalasan()->exists(),
                'RekonsiliasiCheck' => $utangUsaha->rekonsiliasiUtangUsaha()->exists(),
                'ProsedurAltCheck' => $utangUsaha->prosedurAlternatif()->exists(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi utang usaha berhasil dihapus.',
        ]);
    }

    private function KonfirmasiCheck(UtangUsaha $utangUsaha): void
    {
        $hasKonfirmasi = $utangUsaha->konfirmasiUtangUsaha()->exists();
        $utangUsaha->updateQuietly([
            'KonfirmasiCheck' => $hasKonfirmasi,
        ]);
    }
}