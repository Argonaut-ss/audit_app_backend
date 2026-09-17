<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Piutang;
use App\Models\RekapBalasan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekapBalasanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Piutang::query()->with([
            'konfirmasiPiutang:KonfirmasiPiutangID,PiutangID,NamaCustomer,Jumlah',
            'rekapBalasan.konfirmasiPiutang:KonfirmasiPiutangID,PiutangID,NamaCustomer,Jumlah',
        ]);
        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });

        $items = $query->get();

        $items->each(function (Piutang $piutang) {
            $usedKonfirmasiPiutangIds = $piutang->rekapBalasan
                ->pluck('KonfirmasiPiutangID')
                ->filter();

            $piutang->setRelation(
                'konfirmasiPiutangTersedia',
                $piutang->konfirmasiPiutang
                    ->whereNotIn('KonfirmasiPiutangID', $usedKonfirmasiPiutangIds)
                    ->values()
            );
        });

        return response()->json($items);
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

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => [
                'required',
                'array',
                'min:1'
            ],

            'data.*.RekapBalasanID' => [
                'nullable',
                'integer',
                'exists:RekapBalasan,RekapBalasanID',
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

            'data.*.SaldoBB' => ['nullable', 'integer'],
            'data.*.TanggalKirim' => ['nullable', 'date'],
            'data.*.MetodeKirim' => ['nullable', 'string', 'max:255'],
            'data.*.TanggalJawab' => ['nullable', 'date'],
            'data.*.SaldoJawab' => ['nullable', 'integer'],
            'data.*.Selisih' => ['nullable', 'integer'],
            'data.*.Status' => ['nullable', 'in:terbalas,tidak terbalas'],
            'data.*.FileBukti' => ['nullable', 'file'],
            'data.*.NamaFile' => ['nullable', 'string', 'max:255'],
            'data.*.TipeFile' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = $validated['data'];

        /*
        |--------------------------------------------------------------------------
        | 1. Authorization + cek Piutang
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $piutang = Piutang::findOrFail($row['PiutangID']);
            JwbKasus::forUser($request->user())
                ->where('JwbKasusID', $piutang->JwbKasusID)
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cek duplicate KonfirmasiPiutangID
        |--------------------------------------------------------------------------
        */

        $konfirmasiIds = collect($rows)
            ->pluck('KonfirmasiPiutangID')
            ->filter(fn ($id) => !is_null($id))
            ->map(fn ($id) => (string) $id);

        if ($konfirmasiIds->count() !== $konfirmasiIds->unique()->count()) {
            throw ValidationException::withMessages([
                'data' =>
                    'Nama Customer yang sama tidak boleh digunakan pada lebih dari satu baris.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Pastikan KonfirmasiPiutang memang milik Piutang tersebut
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            if (empty($row['KonfirmasiPiutangID'])) {
                continue;
            }

            $exists = Piutang::where(
                    'PiutangID',
                    $row['PiutangID']
                )
                ->whereHas('konfirmasiPiutang', function ($query) use ($row) {
                    $query->where(
                        'KonfirmasiPiutangID',
                        $row['KonfirmasiPiutangID']
                    );
                })
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Konfirmasi piutang tidak sesuai dengan Piutang yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Simpan semuanya dalam SATU transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {

                /*
                |--------------------------------------------------------------------------
                | UPDATE
                |--------------------------------------------------------------------------
                */

                if (!empty($row['RekapBalasanID'])) {

                    $rekap = RekapBalasan::findOrFail(
                        $row['RekapBalasanID']
                    );
                    $updateData = [
                        'KonfirmasiPiutangID' =>
                            $row['KonfirmasiPiutangID'] ?? null,

                        'SaldoBB' =>
                            $row['SaldoBB'] ?? null,

                        'TanggalKirim' =>
                            $row['TanggalKirim'] ?? null,

                        'MetodeKirim' =>
                            $row['MetodeKirim'] ?? null,

                        'TanggalJawab' =>
                            $row['TanggalJawab'] ?? null,

                        'SaldoJawab' =>
                            $row['SaldoJawab'] ?? null,

                        'Selisih' =>
                            $row['Selisih'] ?? null,

                        'Status' =>
                            $row['Status'] ?? null,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | FileBukti hanya diubah kalau ada file baru
                    |--------------------------------------------------------------------------
                    */

                    if (isset($row['FileBukti'])) {

                        $file = $row['FileBukti'];

                        $updateData['FileBukti'] =
                            file_get_contents(
                                $file->getRealPath()
                            );

                        $updateData['NamaFile'] =
                            $file->getClientOriginalName();

                        $updateData['TipeFile'] =
                            $file->getMimeType();
                    }

                    $rekap->fill($updateData);
                    $rekap->save();
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE
                |--------------------------------------------------------------------------
                */

                else {

                    $createData = [
                        'PiutangID' =>
                            $row['PiutangID'],

                        'KonfirmasiPiutangID' =>
                            $row['KonfirmasiPiutangID'] ?? null,

                        'SaldoBB' =>
                            $row['SaldoBB'] ?? null,

                        'TanggalKirim' =>
                            $row['TanggalKirim'] ?? null,

                        'MetodeKirim' =>
                            $row['MetodeKirim'] ?? null,

                        'TanggalJawab' =>
                            $row['TanggalJawab'] ?? null,

                        'SaldoJawab' =>
                            $row['SaldoJawab'] ?? null,

                        'Selisih' =>
                            $row['Selisih'] ?? null,

                        'Status' =>
                            $row['Status'] ?? null,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | FileBukti untuk data baru
                    |--------------------------------------------------------------------------
                    */

                    if (isset($row['FileBukti'])) {

                        $file = $row['FileBukti'];

                        $createData['FileBukti'] =
                            file_get_contents(
                                $file->getRealPath()
                            );

                        $createData['NamaFile'] =
                            $file->getClientOriginalName();

                        $createData['TipeFile'] =
                            $file->getMimeType();
                    }

                    RekapBalasan::create($createData);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update RekapCheck
            |--------------------------------------------------------------------------
            */

            $piutangIds = collect($rows)
                ->pluck('PiutangID')
                ->unique();

            foreach ($piutangIds as $piutangId) {

                $piutang = Piutang::find($piutangId);

                if ($piutang) {
                    $piutang->updateQuietly([
                        'RekapCheck' =>
                            $piutang->rekapBalasan()->exists(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data rekap balasan berhasil disimpan.',
        ]);
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

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => [
                'required',
                'array',
                'min:1'
            ],
            'data.*.RekapBalasanID' => [
                'nullable',
                'integer',
                'exists:RekapBalasan,RekapBalasanID',
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

            'data.*.SaldoBB' => ['nullable', 'integer'],
            'data.*.TanggalKirim' => ['nullable', 'date'],
            'data.*.MetodeKirim' => ['nullable', 'string', 'max:255'],
            'data.*.TanggalJawab' => ['nullable', 'date'],
            'data.*.SaldoJawab' => ['nullable', 'integer'],
            'data.*.Selisih' => ['nullable', 'integer'],
            'data.*.Status' => ['nullable', 'in:terbalas,tidak terbalas'],
            'data.*.NamaFile' => ['nullable', 'string', 'max:255'],
            'data.*.TipeFile' => ['nullable', 'string', 'max:255'],
        ]);
        $rows = $validated['data'];

        /*
        |--------------------------------------------------------------------------
        | 1. Authorization + cek Piutang
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $piutang = Piutang::findOrFail($row['PiutangID']);

            JwbKasus::forUser($request->user())
                ->where('JwbKasusID', $piutang->JwbKasusID)
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cek duplicate KonfirmasiPiutangID
        |    berdasarkan kondisi AKHIR yang dikirim frontend
        |--------------------------------------------------------------------------
        */

        $konfirmasiIds = collect($rows)
            ->pluck('KonfirmasiPiutangID')
            ->filter(fn ($id) => !is_null($id))
            ->map(fn ($id) => (string) $id);

        if ($konfirmasiIds->count() !== $konfirmasiIds->unique()->count()) {
            throw ValidationException::withMessages([
                'data' => 'Nama Customer yang sama tidak boleh digunakan pada lebih dari satu baris.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Pastikan setiap KonfirmasiPiutang memang milik Piutang tersebut
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            if (empty($row['KonfirmasiPiutangID'])) {
                continue;
            }
            $exists = Piutang::where('PiutangID', $row['PiutangID'])
                ->whereHas('konfirmasiPiutang', function ($query) use ($row) {
                    $query->where(
                        'KonfirmasiPiutangID',
                        $row['KonfirmasiPiutangID']
                    );
                })
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'data' => 'Konfirmasi piutang tidak sesuai dengan Piutang yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Simpan semuanya dalam SATU transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {

                /*
                * Kalau RekapBalasanID ada:
                * UPDATE data lama
                */

                if (!empty($row['RekapBalasanID'])) {
                    $rekap = RekapBalasan::findOrFail(
                        $row['RekapBalasanID']
                    );

                    $rekap->fill([
                        'KonfirmasiPiutangID' => $row['KonfirmasiPiutangID'] ?? null,
                        'SaldoBB' => $row['SaldoBB'] ?? null,
                        'TanggalKirim' => $row['TanggalKirim'] ?? null,
                        'MetodeKirim' => $row['MetodeKirim'] ?? null,
                        'TanggalJawab' => $row['TanggalJawab'] ?? null,
                        'SaldoJawab' => $row['SaldoJawab'] ?? null,
                        'Selisih' => $row['Selisih'] ?? null,
                        'Status' => $row['Status'] ?? null,
                        'NamaFile' => $row['NamaFile'] ?? null,
                        'TipeFile' => $row['TipeFile'] ?? null,
                    ]);
                    $rekap->save();
                }

                /*
                * Kalau RekapBalasanID null:
                * CREATE data baru
                */
                else {
                    RekapBalasan::create([
                        'PiutangID' => $row['PiutangID'],
                        'KonfirmasiPiutangID' => $row['KonfirmasiPiutangID'] ?? null,
                        'SaldoBB' => $row['SaldoBB'] ?? null,
                        'TanggalKirim' => $row['TanggalKirim'] ?? null,
                        'MetodeKirim' => $row['MetodeKirim'] ?? null,
                        'TanggalJawab' => $row['TanggalJawab'] ?? null,
                        'SaldoJawab' => $row['SaldoJawab'] ?? null,
                        'Selisih' => $row['Selisih'] ?? null,
                        'Status' => $row['Status'] ?? null,
                        'NamaFile' => $row['NamaFile'] ?? null,
                        'TipeFile' => $row['TipeFile'] ?? null,
                    ]);
                }
            }

            /*
            * Update RekapCheck untuk Piutang yang terlibat
            */

            $piutangIds = collect($rows)
                ->pluck('PiutangID')
                ->unique();

            foreach ($piutangIds as $piutangId) {
                $piutang = Piutang::find($piutangId);
                if ($piutang) {
                    $piutang->updateQuietly([
                        'RekapCheck' =>
                            $piutang->rekapBalasan()->exists(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data rekap balasan berhasil disimpan.',
        ]);
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
