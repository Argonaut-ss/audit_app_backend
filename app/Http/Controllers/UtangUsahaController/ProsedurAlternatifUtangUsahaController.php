<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\UtangUsaha;
use App\Models\UtangUsaha\ProsedurAlternatifUtangUsaha;
use App\Models\UtangUsaha\RekapBalasanUtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProsedurAlternatifUtangUsahaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UtangUsaha::query()->with([
            'konfirmasiUtangUsaha:KonfirmasiUtangUsahaID,UtangUsahaID,NamaCustomer',
            'prosedurAlternatif.konfirmasiUtangUsaha:KonfirmasiUtangUsahaID,UtangUsahaID,NamaCustomer',
        ]);
        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });
        $items = $query->get();
        $items->each(function (UtangUsaha $utangUsaha) {
            $usedCustomerNames = $utangUsaha->prosedurAlternatif
                ->map(fn (ProsedurAlternatifUtangUsaha $item) => $item->konfirmasiUtangUsaha?->NamaCustomer)
                ->filter();
            $utangUsaha->setRelation(
                'konfirmasiUtangUsahaTersedia',
                $utangUsaha->konfirmasiUtangUsaha
                    ->map(function ($konfirmasiUtangUsaha) use ($utangUsaha) {
                        $konfirmasiUtangUsaha->SaldoBB = $this->saldoBB(
                            $utangUsaha,
                            $konfirmasiUtangUsaha->KonfirmasiUtangUsahaID
                        );

                        return $konfirmasiUtangUsaha;
                    })
                    ->whereNotIn('NamaCustomer', $usedCustomerNames)
                    ->values()
            );
        });

        return response()->json($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = ProsedurAlternatifUtangUsaha::with(['utangUsaha', 'konfirmasiUtangUsaha'])->findOrFail($id);
        $this->authorizeUtangUsaha($request, $data->utangUsaha);

        return response()->json($this->serializeItem($data));
    }

    public function file(Request $request, int $id)
    {
        $item = ProsedurAlternatifUtangUsaha::with('utangUsaha')->findOrFail($id);
        $this->authorizeUtangUsaha($request, $item->utangUsaha);
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
            'UtangUsahaID' => ['required', 'exists:UtangUsaha,UtangUsahaID'],
            'KonfirmasiUtangUsahaID' => ['nullable', 'exists:KonfirmasiUtangUsaha,KonfirmasiUtangUsahaID'],
            'SaldoAkhir' => ['nullable', 'integer'],
            'KonfirmasiBayar' => ['nullable', 'boolean'],
            'BuktiBayar' => ['nullable', 'string', 'max:255'],
            'SaldoBata' => ['nullable', 'integer'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);
        $utangUsaha = UtangUsaha::findOrFail($validated['UtangUsahaID']);
        $this->authorizeUtangUsaha($request, $utangUsaha);
        $konfirmasiId = $validated['KonfirmasiUtangUsahaID'] ?? null;

        if ($konfirmasiId !== null) {
            $this->resolveKonfirmasi($utangUsaha, $konfirmasiId);

            if ($utangUsaha->prosedurAlternatif()
                ->where('KonfirmasiUtangUsahaID', $konfirmasiId)
                ->exists()) {
                return response()->json([
                    'message' => 'Konfirmasi utang usaha ini sudah digunakan untuk Utang Usaha ini.',
                ], 422);
            }
        }

        $this->storeFile($request, $validated);
        $item = $utangUsaha->prosedurAlternatif()->create($validated);
        $this->prosedurAlternatifCheck($utangUsaha);

        return response()->json($this->serializeItem($item->load(['utangUsaha', 'konfirmasiUtangUsaha'])), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProsedurAlternatifUtangUsaha::with('utangUsaha')->findOrFail($id);
        $utangUsaha = $item->utangUsaha;
        $this->authorizeUtangUsaha($request, $utangUsaha);

        $validated = $request->validate([
            'KonfirmasiUtangUsahaID' => ['sometimes', 'nullable', 'exists:KonfirmasiUtangUsaha,KonfirmasiUtangUsahaID'],
            'SaldoAkhir' => ['sometimes', 'nullable', 'integer'],
            'KonfirmasiBayar' => ['nullable', 'boolean'],
            'BuktiBayar' => ['nullable', 'string', 'max:255'],
            'SaldoBata' => ['nullable', 'integer'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('KonfirmasiUtangUsahaID', $validated) && $validated['KonfirmasiUtangUsahaID'] !== null) {
            $konfirmasi = $this->resolveKonfirmasi($utangUsaha, $validated['KonfirmasiUtangUsahaID']);
            $duplicate = $utangUsaha->prosedurAlternatif()
                ->where('KonfirmasiUtangUsahaID', $konfirmasi->KonfirmasiUtangUsahaID)
                ->where('ProsedurAlternatifUtangUsahaID', '!=', $item->getKey())
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Konfirmasi utang usaha ini sudah digunakan untuk Utang Usaha ini.',
                ], 422);
            }
        }
        $this->storeFile($request, $validated);
        $item->fill($validated);
        $item->save();

        return response()->json($this->serializeItem($item->fresh()->load(['utangUsaha', 'konfirmasiUtangUsaha'])));
    }

    public function bulkSave(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDASI BENTUK REQUEST
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'data' => [
                'required',
                'array',
                'min:1',
            ],

            'data.*.ProsedurAlternatifUtangUsahaID' => [
                'nullable',
                'integer',
                'exists:ProsedurAlternatifUtangUsaha,ProsedurAlternatifUtangUsahaID',
            ],

            'data.*.UtangUsahaID' => [
                'required',
                'integer',
                'exists:UtangUsaha,UtangUsahaID',
            ],

            'data.*.KonfirmasiUtangUsahaID' => [
                'nullable',
                'integer',
                'exists:KonfirmasiUtangUsaha,KonfirmasiUtangUsahaID',
            ],

            'data.*.SaldoAkhir' => [
                'nullable',
                'integer',
            ],

            'data.*.KonfirmasiBayar' => [
                'nullable',
                'boolean',
            ],

            'data.*.BuktiBayar' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.SaldoBata' => [
                'nullable',
                'integer',
            ],

            'data.*.FileBukti' => [
                'nullable',
                'file',
            ],

            'data.*.NamaFile' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.TipeFile' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. DATA TEMPORARY DI MEMORY
        |--------------------------------------------------------------------------
        |
        | Semua proses validasi berikutnya dilakukan terhadap data ini.
        | Belum ada perubahan ke database.
        |
        */

        $rows = collect($validated['data']);

        /*
        |--------------------------------------------------------------------------
        | 3. AMBIL SEMUA UTANG USAHA YANG TERLIBAT
        |--------------------------------------------------------------------------
        */

        $utangUsahaIds = $rows
            ->pluck('UtangUsahaID')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $utangUsahas = UtangUsaha::whereIn('UtangUsahaID', $utangUsahaIds)
            ->get()
            ->keyBy('UtangUsahaID');

        /*
        |--------------------------------------------------------------------------
        | 4. AUTHORIZATION PER UTANG USAHA
        |--------------------------------------------------------------------------
        */

        foreach ($utangUsahas as $utangUsaha) {
            $this->authorizeUtangUsaha(
                $request,
                $utangUsaha
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. AMBIL SEMUA DATA PROSEDUR ALTERNATIF LAMA
        |--------------------------------------------------------------------------
        |
        | Hanya ambil data dari UtangUsahaID yang sedang diproses.
        |
        */

        $existingItems = ProsedurAlternatifUtangUsaha::whereIn(
                'UtangUsahaID',
                $utangUsahaIds
            )
            ->get()
            ->keyBy('ProsedurAlternatifUtangUsahaID');

        /*
        |--------------------------------------------------------------------------
        | 6. VALIDASI ProsedurAlternatifUtangUsahaID
        |--------------------------------------------------------------------------
        |
        | ID yang dikirim HARUS berasal dari UtangUsahaID yang sama.
        |
        | Contoh:
        |
        | ProsedurAlternatifUtangUsahaID 10 -> UtangUsahaID 1
        |
        | Tidak boleh dikirim:
        |
        | ProsedurAlternatifUtangUsahaID 10 + UtangUsahaID 2
        |
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            $prosedurId = $row['ProsedurAlternatifUtangUsahaID'] ?? null;

            if ($prosedurId === null) {
                continue;
            }

            $prosedurId = (int) $prosedurId;
            $utangUsahaId = (int) $row['UtangUsahaID'];

            $existing = $existingItems->get($prosedurId);

            if (!$existing) {
                throw ValidationException::withMessages([
                    "data.$index.ProsedurAlternatifUtangUsahaID" =>
                        'Prosedur alternatif tidak ditemukan.',
                ]);
            }

            if ((int) $existing->UtangUsahaID !== $utangUsahaId) {
                throw ValidationException::withMessages([
                    "data.$index.ProsedurAlternatifUtangUsahaID" =>
                        'Prosedur alternatif tidak sesuai dengan Utang Usaha yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. VALIDASI SEMUA KonfirmasiUtangUsahaID
        |--------------------------------------------------------------------------
        |
        | KonfirmasiUtangUsahaID juga HARUS berasal dari UtangUsahaID yang sama.
        |--------------------------------------------------------------------------
        */

        $konfirmasiIds = $rows
            ->pluck('KonfirmasiUtangUsahaID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $konfirmasiUtangUsaha = collect();

        if ($konfirmasiIds->isNotEmpty()) {
            $konfirmasiUtangUsaha = \App\Models\UtangUsaha\KonfirmasiUtangUsaha::whereIn(
                    'KonfirmasiUtangUsahaID',
                    $konfirmasiIds
                )
                ->get()
                ->keyBy('KonfirmasiUtangUsahaID');
        }

        foreach ($rows as $index => $row) {

            $konfirmasiId = $row['KonfirmasiUtangUsahaID'] ?? null;

            if ($konfirmasiId === null) {
                continue;
            }

            $konfirmasiId = (int) $konfirmasiId;
            $utangUsahaId = (int) $row['UtangUsahaID'];

            $konfirmasi = $konfirmasiUtangUsaha->get($konfirmasiId);

            if (!$konfirmasi) {
                throw ValidationException::withMessages([
                    "data.$index.KonfirmasiUtangUsahaID" =>
                        'Konfirmasi utang usaha tidak ditemukan.',
                ]);
            }

            if ((int) $konfirmasi->UtangUsahaID !== $utangUsahaId) {
                throw ValidationException::withMessages([
                    "data.$index.KonfirmasiUtangUsahaID" =>
                        'Konfirmasi utang usaha tidak sesuai dengan Utang Usaha yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. BUAT STATE AKHIR SEMENTARA PER UTANG USAHA
        |--------------------------------------------------------------------------
        |
        | Konsep:
        |
        | Database:
        |
        | Utang Usaha 1:
        |   PA 1 - Customer A
        |   PA 2 - Customer B
        |
        | Request:
        |   PA 1 - Customer C
        |
        | Maka state akhir sementara:
        |
        | Utang Usaha 1:
        |   PA 1 - Customer C
        |
        | PA 2 akan dianggap DELETE.
        |
        | Utang Usaha lain tidak disentuh.
        |--------------------------------------------------------------------------
        */

        $finalState = collect();

        foreach ($utangUsahaIds as $utangUsahaId) {
            $finalState->put(
                $utangUsahaId,
                collect()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. MASUKKAN ROW REQUEST KE STATE AKHIR
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            $utangUsahaId = (int) $row['UtangUsahaID'];
            $prosedurId = $row['ProsedurAlternatifUtangUsahaID'] ?? null;

            $data = [
                'UtangUsahaID' => $utangUsahaId,

                'ProsedurAlternatifUtangUsahaID' =>
                    $prosedurId !== null
                        ? (int) $prosedurId
                        : null,

                'KonfirmasiUtangUsahaID' =>
                    $row['KonfirmasiUtangUsahaID'] ?? null,

                'SaldoAkhir' =>
                    $row['SaldoAkhir'] ?? null,

                'KonfirmasiBayar' =>
                    $row['KonfirmasiBayar'] ?? null,

                'BuktiBayar' =>
                    $row['BuktiBayar'] ?? null,

                'SaldoBata' =>
                    $row['SaldoBata'] ?? null,

                /*
                | File hanya diisi kalau file baru dikirim.
                */
                'FileBukti' => null,
                'NamaFile' => null,
                'TipeFile' => null,

                /*
                | Penanda apakah file baru dikirim.
                */
                '_has_new_file' => false,
            ];

            if (
                isset($row['FileBukti']) &&
                $row['FileBukti'] instanceof \Illuminate\Http\UploadedFile
            ) {
                $file = $row['FileBukti'];

                $data['FileBukti'] =
                    file_get_contents(
                        $file->getRealPath()
                    );

                $data['NamaFile'] =
                    $file->getClientOriginalName();

                $data['TipeFile'] =
                    $file->getMimeType();

                $data['_has_new_file'] = true;
            }

            $finalState
                ->get($utangUsahaId)
                ->push($data);
        }

        /*
        |--------------------------------------------------------------------------
        | 10. VALIDASI DUPLICATE KONFIRMASI UTANG USAHA
        |--------------------------------------------------------------------------
        |
        | INI YANG PENTING:
        |
        | Temporary data untuk pengecekan hanya berisi:
        |
        |     KonfirmasiUtangUsahaID
        |
        | Field lain tidak ikut dibandingkan.
        |
        | KonfirmasiUtangUsahaID NULL tidak dianggap duplicate.
        |
        */

        $temporaryKonfirmasiUtangUsahaIds = $rows
            ->pluck('KonfirmasiUtangUsahaID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id);

        if (
            $temporaryKonfirmasiUtangUsahaIds->count() !==
            $temporaryKonfirmasiUtangUsahaIds->unique()->count()
        ) {
            throw ValidationException::withMessages([
                'data' => 'Konfirmasi utang usaha tidak boleh digunakan lebih dari satu kali.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 11. SEMUA VALIDASI SUDAH LOLOS
        |--------------------------------------------------------------------------
        |
        | BARU SEKARANG DATABASE BOLEH DIUBAH.
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $rows,
            $utangUsahaIds,
            $existingItems,
            $finalState
        ) {

            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            |
            | Untuk setiap UtangUsahaID:
            |
            | semua row lama yang tidak ada di request = DELETE.
            |
            */

            foreach ($utangUsahaIds as $utangUsahaId) {

                $submittedIds = $finalState
                    ->get($utangUsahaId)
                    ->pluck('ProsedurAlternatifUtangUsahaID')
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                $existingForUtangUsaha = $existingItems
                    ->filter(
                        fn ($item) =>
                            (int) $item->UtangUsahaID === (int) $utangUsahaId
                    );

                foreach ($existingForUtangUsaha as $existing) {

                    if (
                        !$submittedIds->contains(
                            (int) $existing->ProsedurAlternatifUtangUsahaID
                        )
                    ) {
                        $existing->delete();
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE / CREATE
            |--------------------------------------------------------------------------
            */

            foreach ($finalState as $utangUsahaId => $state) {

                foreach ($state as $row) {

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE
                    |--------------------------------------------------------------------------
                    */

                    if ($row['ProsedurAlternatifUtangUsahaID'] !== null) {

                        $item = $existingItems->get(
                            $row['ProsedurAlternatifUtangUsahaID']
                        );

                        /*
                        | Harusnya selalu ada karena sudah divalidasi
                        | sebelum transaction.
                        */
                        if (!$item) {
                            throw new \RuntimeException(
                                'Prosedur alternatif tidak ditemukan.'
                            );
                        }

                        $data = [
                            'KonfirmasiUtangUsahaID' =>
                                $row['KonfirmasiUtangUsahaID'],

                            'SaldoAkhir' =>
                                $row['SaldoAkhir'],

                            'KonfirmasiBayar' =>
                                $row['KonfirmasiBayar'],

                            'BuktiBayar' =>
                                $row['BuktiBayar'],

                            'SaldoBata' =>
                                $row['SaldoBata'],
                        ];

                        /*
                        | File lama tetap dipertahankan
                        | kalau tidak ada file baru.
                        */
                        if ($row['_has_new_file']) {

                            $data['FileBukti'] =
                                $row['FileBukti'];

                            $data['NamaFile'] =
                                $row['NamaFile'];

                            $data['TipeFile'] =
                                $row['TipeFile'];
                        }

                        $item->fill($data);
                        $item->save();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE
                    |--------------------------------------------------------------------------
                    */

                    else {

                        $data = [
                            'UtangUsahaID' =>
                                $utangUsahaId,

                            'KonfirmasiUtangUsahaID' =>
                                $row['KonfirmasiUtangUsahaID'],

                            'SaldoAkhir' =>
                                $row['SaldoAkhir'],

                            'KonfirmasiBayar' =>
                                $row['KonfirmasiBayar'],

                            'BuktiBayar' =>
                                $row['BuktiBayar'],

                            'SaldoBata' =>
                                $row['SaldoBata'],
                        ];

                        if ($row['_has_new_file']) {

                            $data['FileBukti'] =
                                $row['FileBukti'];

                            $data['NamaFile'] =
                                $row['NamaFile'];

                            $data['TipeFile'] =
                                $row['TipeFile'];
                        }

                        ProsedurAlternatifUtangUsaha::create($data);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE ProsedurAltCheck
            |--------------------------------------------------------------------------
            */

            foreach ($utangUsahaIds as $utangUsahaId) {

                $utangUsaha = UtangUsaha::find($utangUsahaId);

                if ($utangUsaha) {
                    $this->prosedurAlternatifCheck($utangUsaha);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data prosedur alternatif berhasil disimpan.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = ProsedurAlternatifUtangUsaha::with('utangUsaha')->findOrFail($id);
        $utangUsaha = $item->utangUsaha;
        $this->authorizeUtangUsaha($request, $utangUsaha);

        $item->delete();
        $this->prosedurAlternatifCheck($utangUsaha);

        return response()->json(['message' => 'Prosedur alternatif deleted successfully']);
    }

    private function authorizeUtangUsaha(Request $request, ?UtangUsaha $utangUsaha): void
    {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $utangUsaha?->JwbKasusID)
            ->firstOrFail();
    }

    private function resolveKonfirmasi(UtangUsaha $utangUsaha, int $id)
    {
        return $utangUsaha->konfirmasiUtangUsaha()
            ->where('KonfirmasiUtangUsahaID', $id)
            ->firstOrFail();
    }

    private function saldoBB(UtangUsaha $utangUsaha, ?int $konfirmasiUtangUsahaId): ?int
    {
        if (!$konfirmasiUtangUsahaId) {
            return null;
        }

        return RekapBalasanUtangUsaha::query()
            ->where('UtangUsahaID', $utangUsaha->UtangUsahaID)
            ->where('KonfirmasiUtangUsahaID', $konfirmasiUtangUsahaId)
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

    private function serializeItem(ProsedurAlternatifUtangUsaha $item): array
    {
        $item->loadMissing('konfirmasiUtangUsaha');

        return [
            'ProsedurAlternatifUtangUsahaID' => $item->ProsedurAlternatifUtangUsahaID,
            'UtangUsahaID' => $item->UtangUsahaID,
            'KonfirmasiUtangUsahaID' => $item->KonfirmasiUtangUsahaID,
            'NamaCustomer' => $item->konfirmasiUtangUsaha?->NamaCustomer,
            'SaldoBB' => $this->saldoBB($item->utangUsaha, $item->KonfirmasiUtangUsahaID),
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

    private function prosedurAlternatifCheck(UtangUsaha $utangUsaha): void
    {
        $utangUsaha->updateQuietly([
            'ProsedurAltCheck' => $utangUsaha->prosedurAlternatif()->exists(),
        ]);
    }
}