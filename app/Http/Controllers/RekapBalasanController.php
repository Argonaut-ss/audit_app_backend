<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Piutang;
use App\Models\RekapBalasan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RekapBalasanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Piutang::query()->with([
            'konfirmasiPiutang' => function ($query) {
                $query
                    ->select('KonfirmasiPiutangID', 'PiutangID', 'NamaCustomer', 'Jumlah')
                    ->whereDoesntHave('rekapBalasan');
            },
            'rekapBalasan.konfirmasiPiutang:KonfirmasiPiutangID,PiutangID,NamaCustomer,Jumlah',
        ]);
        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });

        return response()->json($query->get());
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = RekapBalasan::with(['piutang', 'konfirmasiPiutang'])->findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $data->piutang?->JwbKasusID)
            ->firstOrFail();

        return response()->json($data);
    }

    public function file(Request $request, int $id)
    {
        $item = RekapBalasan::findOrFail($id);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $item->piutang?->JwbKasusID)
            ->firstOrFail();
        if (is_null($item->FileBukti)) {
            return response()->json([
                'success' => false,
                'message' => 'File rekap balasan tidak ditemukan.',
            ], 404);
        }

        $filename = basename($item->NamaFile ?: 'rekap-balasan-file');
        $contentType = $item->TipeFile ?: 'application/octet-stream';

        return response(
            $item->FileBukti,
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
            'PiutangID' => ['required', 'exists:Piutang,PiutangID'],
            'KonfirmasiPiutangID' => ['nullable', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'SaldoBB' => ['nullable', 'integer'],
            'TanggalKirim' => ['nullable', 'date'],
            'MetodeKirim' => ['nullable', 'string', 'max:255'],
            'TanggalJawab' => ['nullable', 'date'],
            'SaldoJawab' => ['nullable', 'integer'],
            'Selisih' => ['nullable', 'integer'],
            'Status' => ['nullable', 'in:terbalas,tidak terbalas'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        $piutang = Piutang::findOrFail($validated['PiutangID']);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang->JwbKasusID)
            ->firstOrFail();

        if (!empty($validated['KonfirmasiPiutangID'])) {
            $konfirmasiPiutang = $piutang->konfirmasiPiutang()
                ->whereKey($validated['KonfirmasiPiutangID'])
                ->whereDoesntHave('rekapBalasan')
                ->exists();

            if (!$konfirmasiPiutang) {
                return response()->json([
                    'message' => 'Konfirmasi piutang tidak tersedia untuk Piutang ini atau sudah digunakan.',
                ], 422);
            }
        }

        $file = $request->file('FileBukti');
        if ($file) {
            $validated['FileBukti'] = file_get_contents($file->getRealPath());
            $validated['NamaFile'] = $file->getClientOriginalName();
            $validated['TipeFile'] = $file->getMimeType();
        }

        $rekap = RekapBalasan::create($validated);
        $this->rekapCheck($piutang);

        return response()->json($rekap->load(['piutang', 'konfirmasiPiutang']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rekap = RekapBalasan::findOrFail($id);
        $piutang = $rekap->piutang;

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang?->JwbKasusID)
            ->firstOrFail();

        $validated = $request->validate([
            'KonfirmasiPiutangID' => ['nullable', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'SaldoBB' => ['nullable', 'integer'],
            'TanggalKirim' => ['nullable', 'date'],
            'MetodeKirim' => ['nullable', 'string', 'max:255'],
            'TanggalJawab' => ['nullable', 'date'],
            'SaldoJawab' => ['nullable', 'integer'],
            'Selisih' => ['nullable', 'integer'],
            'Status' => ['sometimes', 'in:terbalas,tidak terbalas'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('KonfirmasiPiutangID', $validated) && $validated['KonfirmasiPiutangID'] !== null) {
            $konfirmasiPiutang = $piutang->konfirmasiPiutang()
                ->whereKey($validated['KonfirmasiPiutangID'])
                ->whereDoesntHave('rekapBalasan', function ($query) use ($rekap) {
                    $query->where('RekapBalasanID', '!=', $rekap->getKey());
                })
                ->exists();

            if (!$konfirmasiPiutang) {
                return response()->json([
                    'message' => 'Konfirmasi piutang tidak tersedia untuk Piutang ini atau sudah digunakan.',
                ], 422);
            }
        }

        $file = $request->file('FileBukti');
        if ($file) {
            $validated['FileBukti'] = file_get_contents($file->getRealPath());
            $validated['NamaFile'] = $file->getClientOriginalName();
            $validated['TipeFile'] = $file->getMimeType();
        }

        $rekap->fill($validated);
        $rekap->save();

        return response()->json($rekap->fresh()->load(['piutang', 'konfirmasiPiutang']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $rekap = RekapBalasan::findOrFail($id);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $rekap->piutang?->JwbKasusID)
            ->firstOrFail();

        $rekap->delete();
        $this->rekapCheck($rekap->piutang);

        return response()->json(['message' => 'Rekap balasan deleted successfully']);
    }

    private function rekapCheck(?Piutang $piutang): void
    {
        if ($piutang) {
            $piutang->updateQuietly([
                'RekapCheck' => $piutang->rekapBalasan()->exists(),
            ]);
        }
    }
}
