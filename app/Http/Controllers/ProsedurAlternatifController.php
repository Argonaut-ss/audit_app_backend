<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Piutang;
use App\Models\ProsedurAlternatif;
use App\Models\RekapBalasan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProsedurAlternatifController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Piutang::query()->with([
            'konfirmasiPiutang:KonfirmasiPiutangID,PiutangID,NamaCustomer',
            'prosedurAlternatif.konfirmasiPiutang:KonfirmasiPiutangID,PiutangID,NamaCustomer',
        ]);
        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });

        $items = $query->get();

        $items->each(function (Piutang $piutang) {
            $usedCustomerNames = $piutang->prosedurAlternatif
                ->map(fn (ProsedurAlternatif $item) => $item->konfirmasiPiutang?->NamaCustomer)
                ->filter();

            $piutang->setRelation(
                'konfirmasiPiutangTersedia',
                $piutang->konfirmasiPiutang
                    ->map(function ($konfirmasiPiutang) use ($piutang) {
                        $konfirmasiPiutang->SaldoBB = $this->saldoBB(
                            $piutang,
                            $konfirmasiPiutang->KonfirmasiPiutangID
                        );

                        return $konfirmasiPiutang;
                    })
                    ->whereNotIn('NamaCustomer', $usedCustomerNames)
                    ->values()
            );
        });

        return response()->json($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = ProsedurAlternatif::with(['piutang', 'konfirmasiPiutang'])->findOrFail($id);

        $this->authorizePiutang($request, $data->piutang);

        return response()->json($this->serializeItem($data));
    }

    public function file(Request $request, int $id)
    {
        $item = ProsedurAlternatif::with('piutang')->findOrFail($id);
        $this->authorizePiutang($request, $item->piutang);

        if (is_null($item->FileBukti)) {
            return response()->json([
                'success' => false,
                'message' => 'File prosedur alternatif tidak ditemukan.',
            ], 404);
        }

        $filename = basename($item->NamaFile ?: 'prosedur-alternatif-file');
        $contentType = $item->TipeFile ?: 'application/octet-stream';

        return response($item->FileBukti, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PiutangID' => ['required', 'exists:Piutang,PiutangID'],
            'KonfirmasiPiutangID' => ['required', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'SaldoAkhir' => ['nullable', 'integer'],
            'KonfirmasiBayar' => ['nullable', 'boolean'],
            'BuktiBayar' => ['nullable', 'string', 'max:255'],
            'SaldoBata' => ['nullable', 'integer'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        $piutang = Piutang::findOrFail($validated['PiutangID']);
        $this->authorizePiutang($request, $piutang);
        $konfirmasi = $this->resolveKonfirmasi($piutang, $validated['KonfirmasiPiutangID']);

        if ($piutang->prosedurAlternatif()->where('KonfirmasiPiutangID', $konfirmasi->KonfirmasiPiutangID)->exists()) {
            return response()->json([
                'message' => 'Konfirmasi piutang ini sudah digunakan untuk Piutang ini.',
            ], 422);
        }

        $this->storeFile($request, $validated);

        $item = $piutang->prosedurAlternatif()->create($validated);
        $this->prosedurAlternatifCheck($piutang);

        return response()->json($this->serializeItem($item->load(['piutang', 'konfirmasiPiutang'])), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProsedurAlternatif::with('piutang')->findOrFail($id);
        $piutang = $item->piutang;
        $this->authorizePiutang($request, $piutang);

        $validated = $request->validate([
            'KonfirmasiPiutangID' => ['sometimes', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'SaldoAkhir' => ['sometimes', 'nullable', 'integer'],
            'KonfirmasiBayar' => ['nullable', 'boolean'],
            'BuktiBayar' => ['nullable', 'string', 'max:255'],
            'SaldoBata' => ['nullable', 'integer'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('KonfirmasiPiutangID', $validated)) {
            $konfirmasi = $this->resolveKonfirmasi($piutang, $validated['KonfirmasiPiutangID']);
            $duplicate = $piutang->prosedurAlternatif()
                ->where('KonfirmasiPiutangID', $konfirmasi->KonfirmasiPiutangID)
                ->where('ProsedurAlternatifID', '!=', $item->getKey())
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Konfirmasi piutang ini sudah digunakan untuk Piutang ini.',
                ], 422);
            }

        }

        $this->storeFile($request, $validated);
        $item->fill($validated);
        $item->save();

        return response()->json($this->serializeItem($item->fresh()->load(['piutang', 'konfirmasiPiutang'])));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = ProsedurAlternatif::with('piutang')->findOrFail($id);
        $piutang = $item->piutang;
        $this->authorizePiutang($request, $piutang);

        $item->delete();
        $this->prosedurAlternatifCheck($piutang);

        return response()->json(['message' => 'Prosedur alternatif deleted successfully']);
    }

    private function authorizePiutang(Request $request, ?Piutang $piutang): void
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $piutang?->JwbKasusID)
            ->firstOrFail();
    }

    private function resolveKonfirmasi(Piutang $piutang, int $id)
    {
        return $piutang->konfirmasiPiutang()
            ->where('KonfirmasiPiutangID', $id)
            ->firstOrFail();
    }

    private function saldoBB(Piutang $piutang, int $konfirmasiPiutangId): ?int
    {
        return RekapBalasan::query()
            ->where('PiutangID', $piutang->PiutangID)
            ->where('KonfirmasiPiutangID', $konfirmasiPiutangId)
            ->value('SaldoBB');
    }

    private function storeFile(Request $request, array &$validated): void
    {
        $file = $request->file('FileBukti');
        if ($file) {
            $validated['FileBukti'] = file_get_contents($file->getRealPath());
            $validated['NamaFile'] = $file->getClientOriginalName();
            $validated['TipeFile'] = $file->getMimeType();
        }
    }

    private function serializeItem(ProsedurAlternatif $item): array
    {
        $item->loadMissing('konfirmasiPiutang');

        return [
            'ProsedurAlternatifID' => $item->ProsedurAlternatifID,
            'PiutangID' => $item->PiutangID,
            'KonfirmasiPiutangID' => $item->KonfirmasiPiutangID,
            'NamaCustomer' => $item->konfirmasiPiutang?->NamaCustomer,
            'SaldoBB' => $this->saldoBB($item->piutang, $item->KonfirmasiPiutangID),
            'SaldoAkhir' => $item->SaldoAkhir,
            'KonfirmasiBayar' => $item->KonfirmasiBayar,
            'BuktiBayar' => $item->BuktiBayar,
            'SaldoBata' => $item->SaldoBata,
            'NamaFile' => $item->NamaFile,
            'TipeFile' => $item->TipeFile,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function prosedurAlternatifCheck(Piutang $piutang): void
    {
        $piutang->updateQuietly([
            'ProsedurAltCheck' => $piutang->prosedurAlternatif()->exists(),
        ]);
    }
}