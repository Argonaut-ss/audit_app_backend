<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\AserLain\KonfirmasiAsetLain;
use App\Models\AsetLain\AsetLain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonfirmasiAsetLainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KonfirmasiAsetLain::with('asetLain')
            ->whereHas('asetLain', function ($query) use ($request) {
                $query->whereHas('JwbKasus', function ($query) use ($request) {
                    $query->forUser($request->user());
                });
            })
            ->orderByDesc('KonfirmasiAsetLainID')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $konfirmasiAsetLain = KonfirmasiAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiAsetLain->asetLain->JwbKasusID)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $konfirmasiAsetLain,
        ]);
    }

    public function file(Request $request, $id)
    {
        $konfirmasiAsetLain = KonfirmasiAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiAsetLain->asetLain->JwbKasusID)
            ->firstOrFail();

        if (is_null($konfirmasiAsetLain->File)) {
            return response()->json([
                'success' => false,
                'message' => 'File konfirmasi aset lain tidak ditemukan.',
            ], 404);
        }

        $filename = basename($konfirmasiAsetLain->NamaFile ?: 'konfirmasi-aset-lain-file');
        $contentType = $konfirmasiAsetLain->TipeFile ?: 'application/octet-stream';

        $fileContent = $konfirmasiAsetLain->File;

        return response(
            $fileContent,
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => [
                'required',
                'integer',
                'exists:aset_lain,AsetLainID',
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

        $asetLain = AsetLain::findOrFail($validated['AsetLainID']);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetLain->JwbKasusID)
            ->firstOrFail();

        $item = new KonfirmasiAsetLain;
        $item->AsetLainID = $validated['AsetLainID'];
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
        $this->KonfirmasiCheck($asetLain);

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi aset lain berhasil disimpan.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $konfirmasiAsetLain = KonfirmasiAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiAsetLain->asetLain->JwbKasusID)
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

        $konfirmasiAsetLain->fill($validated);

        if ($request->hasFile('File')) {
            $file = $request->file('File');
            $fileContent = file_get_contents($file->getRealPath());
            $konfirmasiAsetLain->File = $fileContent;
            $konfirmasiAsetLain->NamaFile = $file->getClientOriginalName();
            $konfirmasiAsetLain->TipeFile = $file->getMimeType();
        }

        $konfirmasiAsetLain->save();
        $this->KonfirmasiCheck($konfirmasiAsetLain->asetLain);

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi aset lain berhasil diperbarui.',
            'data' => $konfirmasiAsetLain->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $konfirmasiAsetLain = KonfirmasiAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $konfirmasiAsetLain->asetLain->JwbKasusID)
            ->firstOrFail();

        $konfirmasiAsetLain->delete();
        $this->KonfirmasiCheck($konfirmasiAsetLain->asetLain);

        return response()->json([
            'success' => true,
            'message' => 'Data konfirmasi aset lain berhasil dihapus.',
        ]);
    }

    private function KonfirmasiCheck(AsetLain $asetLain): void
    {
        $hasKonfirmasi = $asetLain->konfirmasiAsetLain()->exists();
        $asetLain->updateQuietly([
            'KonfirmasiCheck' => $hasKonfirmasi,
        ]);
    }
}