<?php

namespace App\Http\Controllers\PiutangController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\Piutang\Piutang;
use App\Models\Piutang\ProsedurAlternatif;
use App\Models\Piutang\RekapBalasan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'KonfirmasiPiutangID' => ['nullable', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
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
        $konfirmasiId = $validated['KonfirmasiPiutangID'] ?? null;

        if ($konfirmasiId !== null) {
            $this->resolveKonfirmasi($piutang, $konfirmasiId);

            if ($piutang->prosedurAlternatif()
                ->where('KonfirmasiPiutangID', $konfirmasiId)
                ->exists()) {
                return response()->json([
                    'message' => 'Konfirmasi piutang ini sudah digunakan untuk Piutang ini.',
                ], 422);
            }
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
            'KonfirmasiPiutangID' => ['sometimes', 'nullable', 'exists:KonfirmasiPiutang,KonfirmasiPiutangID'],
            'SaldoAkhir' => ['sometimes', 'nullable', 'integer'],
            'KonfirmasiBayar' => ['nullable', 'boolean'],
            'BuktiBayar' => ['nullable', 'string', 'max:255'],
            'SaldoBata' => ['nullable', 'integer'],
            'FileBukti' => ['nullable'],
            'NamaFile' => ['nullable', 'string', 'max:255'],
            'TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('KonfirmasiPiutangID', $validated) && $validated['KonfirmasiPiutangID'] !== null) {
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

            'data.*.ProsedurAlternatifID' => [
                'nullable',
                'integer',
                'exists:ProsedurAlternatif,ProsedurAlternatifID',
            ],

            'data.*.PiutangID' => [
                'required',
                'integer',
                'exists:Piutang,PiutangID',
            ],

            'data.*.KonfirmasiPiutangID' => [
                'nullable',
                'integer',
                'exists:KonfirmasiPiutang,KonfirmasiPiutangID',
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
        | 3. AMBIL SEMUA PIUTANG YANG TERLIBAT
        |--------------------------------------------------------------------------
        */

        $piutangIds = $rows
            ->pluck('PiutangID')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $piutangs = Piutang::whereIn('PiutangID', $piutangIds)
            ->get()
            ->keyBy('PiutangID');

        /*
        |--------------------------------------------------------------------------
        | 4. AUTHORIZATION PER PIUTANG
        |--------------------------------------------------------------------------
        */

        foreach ($piutangs as $piutang) {
            $this->authorizePiutang(
                $request,
                $piutang
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. AMBIL SEMUA DATA PROSEDUR ALTERNATIF LAMA
        |--------------------------------------------------------------------------
        |
        | Hanya ambil data dari PiutangID yang sedang diproses.
        |
        */

        $existingItems = ProsedurAlternatif::whereIn(
                'PiutangID',
                $piutangIds
            )
            ->get()
            ->keyBy('ProsedurAlternatifID');

        /*
        |--------------------------------------------------------------------------
        | 6. VALIDASI ProsedurAlternatifID
        |--------------------------------------------------------------------------
        |
        | ID yang dikirim HARUS berasal dari PiutangID yang sama.
        |
        | Contoh:
        |
        | ProsedurAlternatifID 10 -> PiutangID 1
        |
        | Tidak boleh dikirim:
        |
        | ProsedurAlternatifID 10 + PiutangID 2
        |
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            $prosedurId = $row['ProsedurAlternatifID'] ?? null;

            if ($prosedurId === null) {
                continue;
            }

            $prosedurId = (int) $prosedurId;
            $piutangId = (int) $row['PiutangID'];

            $existing = $existingItems->get($prosedurId);

            if (!$existing) {
                throw ValidationException::withMessages([
                    "data.$index.ProsedurAlternatifID" =>
                        'Prosedur alternatif tidak ditemukan.',
                ]);
            }

            if ((int) $existing->PiutangID !== $piutangId) {
                throw ValidationException::withMessages([
                    "data.$index.ProsedurAlternatifID" =>
                        'Prosedur alternatif tidak sesuai dengan Piutang yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. VALIDASI SEMUA KonfirmasiPiutangID
        |--------------------------------------------------------------------------
        |
        | KonfirmasiPiutangID juga HARUS berasal dari PiutangID yang sama.
        |--------------------------------------------------------------------------
        */

        $konfirmasiIds = $rows
            ->pluck('KonfirmasiPiutangID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $konfirmasiPiutangs = collect();

        if ($konfirmasiIds->isNotEmpty()) {
            $konfirmasiPiutangs = \App\Models\Piutang\KonfirmasiPiutang::whereIn(
                    'KonfirmasiPiutangID',
                    $konfirmasiIds
                )
                ->get()
                ->keyBy('KonfirmasiPiutangID');
        }

        foreach ($rows as $index => $row) {

            $konfirmasiId = $row['KonfirmasiPiutangID'] ?? null;

            if ($konfirmasiId === null) {
                continue;
            }

            $konfirmasiId = (int) $konfirmasiId;
            $piutangId = (int) $row['PiutangID'];

            $konfirmasi = $konfirmasiPiutangs->get($konfirmasiId);

            if (!$konfirmasi) {
                throw ValidationException::withMessages([
                    "data.$index.KonfirmasiPiutangID" =>
                        'Konfirmasi piutang tidak ditemukan.',
                ]);
            }

            if ((int) $konfirmasi->PiutangID !== $piutangId) {
                throw ValidationException::withMessages([
                    "data.$index.KonfirmasiPiutangID" =>
                        'Konfirmasi piutang tidak sesuai dengan Piutang yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. BUAT STATE AKHIR SEMENTARA PER PIUTANG
        |--------------------------------------------------------------------------
        |
        | Konsep:
        |
        | Database:
        |
        | Piutang 1:
        |   PA 1 - Customer A
        |   PA 2 - Customer B
        |
        | Request:
        |   PA 1 - Customer C
        |
        | Maka state akhir sementara:
        |
        | Piutang 1:
        |   PA 1 - Customer C
        |
        | PA 2 akan dianggap DELETE.
        |
        | Piutang lain tidak disentuh.
        |--------------------------------------------------------------------------
        */

        $finalState = collect();

        foreach ($piutangIds as $piutangId) {
            $finalState->put(
                $piutangId,
                collect()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. MASUKKAN ROW REQUEST KE STATE AKHIR
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            $piutangId = (int) $row['PiutangID'];
            $prosedurId = $row['ProsedurAlternatifID'] ?? null;

            $data = [
                'PiutangID' => $piutangId,

                'ProsedurAlternatifID' =>
                    $prosedurId !== null
                        ? (int) $prosedurId
                        : null,

                'KonfirmasiPiutangID' =>
                    $row['KonfirmasiPiutangID'] ?? null,

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
                ->get($piutangId)
                ->push($data);
        }

        /*
        |--------------------------------------------------------------------------
        | 10. VALIDASI DUPLICATE KONFIRMASI PIUTANG
        |--------------------------------------------------------------------------
        |
        | INI YANG PENTING:
        |
        | Temporary data untuk pengecekan hanya berisi:
        |
        |     KonfirmasiPiutangID
        |
        | Field lain tidak ikut dibandingkan.
        |
        | KonfirmasiPiutangID NULL tidak dianggap duplicate.
        |--------------------------------------------------------------------------
        */

        $temporaryKonfirmasiPiutangIds = $rows
            ->pluck('KonfirmasiPiutangID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id);

        if (
            $temporaryKonfirmasiPiutangIds->count() !==
            $temporaryKonfirmasiPiutangIds->unique()->count()
        ) {
            throw ValidationException::withMessages([
                'data' => 'Konfirmasi piutang tidak boleh digunakan lebih dari satu kali.',
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
            $piutangIds,
            $existingItems,
            $finalState
        ) {

            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            |
            | Untuk setiap PiutangID:
            |
            | semua row lama yang tidak ada di request = DELETE.
            |
            */

            foreach ($piutangIds as $piutangId) {

                $submittedIds = $finalState
                    ->get($piutangId)
                    ->pluck('ProsedurAlternatifID')
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                $existingForPiutang = $existingItems
                    ->filter(
                        fn ($item) =>
                            (int) $item->PiutangID === (int) $piutangId
                    );

                foreach ($existingForPiutang as $existing) {

                    if (
                        !$submittedIds->contains(
                            (int) $existing->ProsedurAlternatifID
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

            foreach ($finalState as $piutangId => $state) {

                foreach ($state as $row) {

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE
                    |--------------------------------------------------------------------------
                    */

                    if ($row['ProsedurAlternatifID'] !== null) {

                        $item = $existingItems->get(
                            $row['ProsedurAlternatifID']
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
                            'KonfirmasiPiutangID' =>
                                $row['KonfirmasiPiutangID'],

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
                            'PiutangID' =>
                                $piutangId,

                            'KonfirmasiPiutangID' =>
                                $row['KonfirmasiPiutangID'],

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

                        ProsedurAlternatif::create($data);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE ProsedurAltCheck
            |--------------------------------------------------------------------------
            */

            foreach ($piutangIds as $piutangId) {

                $piutang = Piutang::find($piutangId);

                if ($piutang) {
                    $this->prosedurAlternatifCheck($piutang);
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

    private function saldoBB(Piutang $piutang, ?int $konfirmasiPiutangId): ?int
    {
        if (!$konfirmasiPiutangId) {
            return null;
        }

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