<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\RekapBalasanAsetLain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekapBalasanAsetLainController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $query = AsetLain::query()->with([
            'konfirmasiAsetLain:KonfirmasiAsetLainID,AsetLainID,NamaCustomer,Jumlah',
            'rekapBalasan.konfirmasiAsetLain:KonfirmasiAsetLainID,AsetLainID,NamaCustomer,Jumlah',
        ]);

        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });

        $items = $query->get();
        $items->each(function (AsetLain $asetLain) {
            $usedKonfirmasiAsetLainIds = $asetLain
                ->rekapBalasan
                ->pluck('KonfirmasiAsetLainID')
                ->filter();

            $asetLain->setRelation(
                'konfirmasiAsetLainTersedia',
                $asetLain
                    ->konfirmasiAsetLain
                    ->whereNotIn('KonfirmasiAsetLainID', $usedKonfirmasiAsetLainIds)
                    ->values()
            );
        });

        return response()->json($items);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, int $id): JsonResponse {
        $data = RekapBalasanAsetLain::with([
            'asetLain',
            'konfirmasiAsetLain',
        ])->findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $data->asetLain?->JwbKasusID)
            ->firstOrFail();

        return response()->json($data);
    }

    /*
    |--------------------------------------------------------------------------
    | FILE
    |--------------------------------------------------------------------------
    */

    public function file(
        Request $request,
        int $id
    ) {
        $item = RekapBalasanAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $item->asetLain?->JwbKasusID
            )
            ->firstOrFail();

        if (is_null($item->FileBukti)) {
            return response()->json([
                'success' => false,
                'message' => 'File rekap balasan tidak ditemukan.',
            ], 404);
        }

        $filename = basename(
            $item->NamaFile ?: 'rekap-balasan-file'
        );

        $contentType =
            $item->TipeFile
            ?: 'application/octet-stream';

        return response(
            $item->FileBukti,
            200,
            [
                'Content-Type' =>
                    $contentType,

                'Content-Disposition' =>
                    'attachment; filename="' .
                    addslashes($filename) .
                    '"',

                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'AsetLainID' => [
                'required',
                'exists:aset_lain,AsetLainID',
            ],

            'KonfirmasiAsetLainID' => [
                'nullable',
                'exists:KonfirmasiAsetLain,KonfirmasiAsetLainID',
            ],

            'SaldoBB' => [
                'nullable',
                'integer',
            ],

            'TanggalKirim' => [
                'nullable',
                'date',
            ],

            'MetodeKirim' => [
                'nullable',
                'string',
                'max:255',
            ],

            'TanggalJawab' => [
                'nullable',
                'date',
            ],

            'SaldoJawab' => [
                'nullable',
                'integer',
            ],

            'Selisih' => [
                'nullable',
                'integer',
            ],

            'Status' => [
                'nullable',
                'in:terbalas,tidak terbalas',
            ],

            'FileBukti' => [
                'nullable',
            ],

            'NamaFile' => [
                'nullable',
                'string',
                'max:255',
            ],

            'TipeFile' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $asetLain = AsetLain::findOrFail($validated['AsetLainID']);
        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $asetLain->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Pastikan customer masih tersedia
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['KonfirmasiAsetLainID'])) {
            $konfirmasiAsetLain =
                $asetLain
                    ->konfirmasiAsetLain()
                    ->whereKey(
                        $validated[
                            'KonfirmasiAsetLainID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasanAsetLain'
                    )
                    ->exists();

            if (!$konfirmasiAsetLain) {
                return response()->json([
                    'message' =>
                        'Konfirmasi aset lain tidak tersedia untuk Aset Lain ini atau sudah digunakan.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */

        $file = $request->file(
            'FileBukti'
        );

        if ($file) {
            $validated['FileBukti'] =
                file_get_contents($file->getRealPath());

            $validated['NamaFile'] =
                $file->getClientOriginalName();

            $validated['TipeFile'] =
                $file->getMimeType();
        }

        $rekap = RekapBalasanAsetLain::create($validated);
        $this->rekapCheck($asetLain);

        return response()->json(
            $rekap->load([
                'asetLain',
                'konfirmasiAsetLain',
            ]),
            201
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BULK SAVE
    |--------------------------------------------------------------------------
    |
    | Digunakan sebagai final-state save.
    |
    | RekapBalasanID ada:
    | -> UPDATE row yang sama.
    |
    | RekapBalasanID tidak ada:
    | -> CREATE row baru.
    |
    | Existing RekapBalasanID tidak dikirim lagi:
    | -> DELETE.
    |
    | SWAP:
    |
    | Sebelum:
    |
    | ID 10 -> Customer A
    | ID 11 -> Customer B
    |
    | Payload:
    |
    | ID 10 -> Customer B
    | ID 11 -> Customer A
    |
    | Backend:
    |
    | 1. Validasi FINAL STATE.
    | 2. Release customer existing menjadi NULL.
    | 3. Assign customer final.
    |
    | RekapBalasanID tetap 10 dan 11.
    |
    |--------------------------------------------------------------------------
    */

    public function bulkSave(Request $request): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'data' => [
                'required',
                'array',
                'min:1',
            ],

            'data.*.RekapBalasanAsetLainID' => [
                'nullable',
                'integer',
                'exists:rekap_balasan_aset_lain,RekapBalasanAsetLainID',
            ],

            'data.*.AsetLainID' => [
                'required',
                'integer',
                'exists:aset_lain,AsetLainID',
            ],

            'data.*.KonfirmasiAsetLainID' => [
                'nullable',
                'integer',
                'exists:KonfirmasiAsetLain,KonfirmasiAsetLainID',
            ],

            'data.*.SaldoBB' => [
                'nullable',
                'integer',
            ],

            'data.*.TanggalKirim' => [
                'nullable',
                'date',
            ],

            'data.*.MetodeKirim' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.TanggalJawab' => [
                'nullable',
                'date',
            ],

            'data.*.SaldoJawab' => [
                'nullable',
                'integer',
            ],

            'data.*.Selisih' => [
                'nullable',
                'integer',
            ],

            'data.*.Status' => [
                'nullable',
                'in:terbalas,tidak terbalas',
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

        $rows = collect(
            $validated['data']
        )->values();

        /*
        |--------------------------------------------------------------------------
        | 1. AUTHORIZATION
        |--------------------------------------------------------------------------
        */

        $asetLainIds = $rows
            ->pluck('AsetLainID')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($asetLainIds as $asetLainId) {
            $asetLain = AsetLain::findOrFail($asetLainId);
            JwbKasus::forUser($request->user())
                ->where(
                    'JwbKasusID',
                    $asetLain->JwbKasusID
                )
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CEK DUPLICATE RekapBalasanAsetLainID DALAM PAYLOAD
        |--------------------------------------------------------------------------
        |
        | ID existing yang sama tidak boleh dikirim dua kali.
        |
        */

        $submittedExistingIds = $rows
            ->pluck('RekapBalasanAsetLainID')
            ->filter(
                fn ($id) =>
                    !is_null($id)
                    && $id !== ''
            )
            ->map(fn ($id) => (int) $id);

        if (
            $submittedExistingIds->count() !== $submittedExistingIds
                ->unique()
                ->count()
        ) {
            throw ValidationException::withMessages([
                'data' =>
                    'RekapBalasanAsetLainID yang sama tidak boleh dikirim lebih dari satu kali.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. CEK DUPLICATE CUSTOMER PADA FINAL STATE
        |--------------------------------------------------------------------------
        |
        | Ini pengecekan yang penting.
        |
        | Yang dicek adalah FINAL STATE dari payload,
        | bukan kondisi lama di database.
        |
        | Valid:
        |
        | ID 10 -> A
        | ID 11 -> B
        |
        | Swap juga valid:
        |
        | ID 10 -> B
        | ID 11 -> A
        |
        | Tidak valid:
        |
        | ID 10 -> A
        | ID 11 -> A
        |
        |--------------------------------------------------------------------------
        */

        $konfirmasiKeys = $rows
            ->filter(
                fn ($row) =>
                    !is_null(
                        $row[
                            'KonfirmasiAsetLainID'
                        ] ?? null
                    )
            )
            ->map(
                fn ($row) =>
                    (int) $row['AsetLainID']
                    . ':'
                    . (int) $row[
                        'KonfirmasiAsetLainID'
                    ]
            );

        if (
            $konfirmasiKeys->count() !== $konfirmasiKeys
                ->unique()
                ->count()
        ) {
            throw ValidationException::withMessages([
                'data' =>
                    'Nama Customer yang sama tidak boleh digunakan pada lebih dari satu baris.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. PASTIKAN CUSTOMER MEMANG MILIK ASET LAIN TERSEBUT
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $konfirmasiId =
                $row['KonfirmasiAsetLainID'] ?? null;

            if (is_null($konfirmasiId)) {
                continue;
            }

            $exists = AsetLain::where(
                'AsetLainID',
                $row['AsetLainID']
            )
                ->whereHas(
                    'konfirmasiAsetLain',
                    function ($query) use (
                        $konfirmasiId
                    ) {
                        $query->where(
                            'KonfirmasiAsetLainID',
                            $konfirmasiId
                        );
                    }
                )
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Konfirmasi aset lain tidak sesuai dengan Aset Lain yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. EXISTING REKAP HARUS MILIK ASET LAIN YANG DIKIRIM
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $rekapId =
                $row[
                    'RekapBalasanAsetLainID'
                ] ?? null;

            if (
                is_null(
                    $rekapId
                )
            ) {
                continue;
            }

            $belongsToAsetLain =
                RekapBalasanAsetLain::where(
                    'RekapBalasanAsetLainID',
                    $rekapId
                )
                    ->where(
                        'AsetLainID',
                        $row['AsetLainID']
                    )
                    ->exists();

            if (!$belongsToAsetLain) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Rekap balasan tidak sesuai dengan Aset Lain yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PENTING
        |--------------------------------------------------------------------------
        |
        | Pengecekan seperti berikut SENGAJA TIDAK DIPAKAI lagi:
        |
        | RekapBalasan::where(...)
        |   ->where('KonfirmasiAsetLainID', target)
        |   ->exists();
        |
        | Karena pengecekan terhadap kondisi DATABASE LAMA
        | tersebut akan membuat SWAP ditolak.
        |
        | Final-state duplicate sudah divalidasi di step 3.
        |
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | 6. TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rows,
                $asetLainIds
            ) {
                /*
                |--------------------------------------------------------------------------
                | Diproses PER ASET LAIN
                |--------------------------------------------------------------------------
                */

                foreach ($asetLainIds as $asetLainId) {
                    $rowsForAsetLain =
                        $rows
                            ->filter(
                                fn ($row) =>
                                    (int)
                                    $row[
                                        'AsetLainID'
                                    ]
                                    ===
                                    (int)
                                    $asetLainId
                            )
                            ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID YANG DIKIRIM FE
                    |--------------------------------------------------------------------------
                    */

                    $submittedRekapIds =
                        $rowsForAsetLain
                            ->pluck(
                                'RekapBalasanAsetLainID'
                            )
                            ->filter(
                                fn ($id) =>
                                    !is_null($id)
                                    && $id !== ''
                            )
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            )
                            ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID DI DATABASE
                    |--------------------------------------------------------------------------
                    */

                    $existingRekapIds =
                        RekapBalasanAsetLain::where(
                            'AsetLainID',
                            $asetLainId
                        )
                            ->pluck(
                                'RekapBalasanAsetLainID'
                            );

                    /*
                    |--------------------------------------------------------------------------
                    | DELETE ROW YANG SUDAH DIHAPUS DARI FE
                    |--------------------------------------------------------------------------
                    |
                    | Karena bulkSave dianggap sebagai final state,
                    | existing ID yang tidak lagi dikirim FE
                    | dianggap telah dihapus.
                    |
                    */

                    $idsToDelete =
                        $existingRekapIds
                            ->diff(
                                $submittedRekapIds
                            );

                    if ($idsToDelete ->isNotEmpty()) {
                        RekapBalasanAsetLain::whereIn(
                            'RekapBalasanAsetLainID',
                            $idsToDelete
                        )->delete();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RELEASE CUSTOMER EXISTING
                    |--------------------------------------------------------------------------
                    |
                    | Ini bagian utama supaya SWAP aman.
                    |
                    | Contoh awal:
                    |
                    | ID 10 -> A
                    | ID 11 -> B
                    |
                    | Sementara di transaction:
                    |
                    | ID 10 -> NULL
                    | ID 11 -> NULL
                    |
                    | RekapBalasanID TIDAK berubah.
                    |
                    */

                    if ($submittedRekapIds ->isNotEmpty()) {
                        RekapBalasanAsetLain::where(
                            'AsetLainID',
                            $asetLainId
                        )
                            ->whereIn(
                                'RekapBalasanAsetLainID',
                                $submittedRekapIds
                            )
                            ->update([
                                'KonfirmasiAsetLainID'
                                    => null,
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE / CREATE FINAL STATE
                    |--------------------------------------------------------------------------
                    */

                    foreach ($rowsForAsetLain as $row) {
                        $rekapId =
                            $row['RekapBalasanAsetLainID'] ?? null;

                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE EXISTING
                        |--------------------------------------------------------------------------
                        */

                        if (!is_null($rekapId)) {
                            $rekap =
                                RekapBalasanAsetLain::where(
                                    'RekapBalasanAsetLainID',
                                    $rekapId
                                )
                                    ->where(
                                        'AsetLainID',
                                        $asetLainId
                                    )
                                    ->firstOrFail();

                            $updateData = [
                                'KonfirmasiAsetLainID' =>
                                    $row[
                                        'KonfirmasiAsetLainID'
                                    ] ?? null,

                                'SaldoBB' =>
                                    $row[
                                        'SaldoBB'
                                    ] ?? null,

                                'TanggalKirim' =>
                                    $row[
                                        'TanggalKirim'
                                    ] ?? null,

                                'MetodeKirim' =>
                                    $row[
                                        'MetodeKirim'
                                    ] ?? null,

                                'TanggalJawab' =>
                                    $row[
                                        'TanggalJawab'
                                    ] ?? null,

                                'SaldoJawab' =>
                                    $row[
                                        'SaldoJawab'
                                    ] ?? null,

                                'Selisih' =>
                                    $row[
                                        'Selisih'
                                    ] ?? null,

                                'Status' =>
                                    $row[
                                        'Status'
                                    ] ?? null,
                            ];

                            /*
                            |--------------------------------------------------------------------------
                            | FILE EXISTING
                            |--------------------------------------------------------------------------
                            |
                            | File hanya diganti jika frontend
                            | benar-benar mengirim FileBukti baru.
                            |
                            | Kalau tidak ada file baru:
                            | FileBukti, NamaFile dan TipeFile lama
                            | tetap dipertahankan.
                            |
                            */

                            if (isset($row['FileBukti'])) {
                                $file = $row['FileBukti'];
                                $updateData['FileBukti'] =
                                    file_get_contents(
                                        $file ->getRealPath()
                                    );

                                $updateData['NamaFile'] =
                                    $file ->getClientOriginalName();

                                $updateData['TipeFile'] =
                                    $file ->getMimeType();
                            }
                            $rekap->fill($updateData);
                            $rekap->save();
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE NEW
                        |--------------------------------------------------------------------------
                        */

                        else {
                            $createData = [
                                'AsetLainID' =>
                                    $asetLainId,

                                'KonfirmasiAsetLainID' =>
                                    $row[
                                        'KonfirmasiAsetLainID'
                                    ] ?? null,

                                'SaldoBB' =>
                                    $row[
                                        'SaldoBB'
                                    ] ?? null,

                                'TanggalKirim' =>
                                    $row[
                                        'TanggalKirim'
                                    ] ?? null,

                                'MetodeKirim' =>
                                    $row[
                                        'MetodeKirim'
                                    ] ?? null,

                                'TanggalJawab' =>
                                    $row[
                                        'TanggalJawab'
                                    ] ?? null,

                                'SaldoJawab' =>
                                    $row[
                                        'SaldoJawab'
                                    ] ?? null,

                                'Selisih' =>
                                    $row[
                                        'Selisih'
                                    ] ?? null,

                                'Status' =>
                                    $row[
                                        'Status'
                                    ] ?? null,
                            ];

                            /*
                            |--------------------------------------------------------------------------
                            | FILE NEW ROW
                            |--------------------------------------------------------------------------
                            */

                            if (isset($row['FileBukti'])) {
                                $file =
                                    $row[
                                        'FileBukti'
                                    ];

                                $createData[
                                    'FileBukti'
                                ] =
                                    file_get_contents(
                                        $file
                                            ->getRealPath()
                                    );

                                $createData[
                                    'NamaFile'
                                ] =
                                    $file
                                        ->getClientOriginalName();

                                $createData[
                                    'TipeFile'
                                ] =
                                    $file
                                        ->getMimeType();
                            }

                            RekapBalasanAsetLain::create($createData);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE REKAP CHECK
                    |--------------------------------------------------------------------------
                    */

                    $asetLain = AsetLain::find($asetLainId);

                    if ($asetLain) {
                        $asetLain->updateQuietly([
                            'RekapCheck' =>
                                $asetLain
                                    ->rekapBalasan()
                                    ->exists(),
                        ]);
                    }
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Data rekap balasan berhasil disimpan.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, int $id): JsonResponse {
        $rekap = RekapBalasanAsetLain::findOrFail($id);
        $asetLain = $rekap->asetLain;

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $asetLain?->JwbKasusID
            )
            ->firstOrFail();

        $validated =
            $request->validate([
                'KonfirmasiAsetLainID' => [
                    'nullable',
                    'exists:KonfirmasiAsetLain,KonfirmasiAsetLainID',
                ],

                'SaldoBB' => [
                    'nullable',
                    'integer',
                ],

                'TanggalKirim' => [
                    'nullable',
                    'date',
                ],

                'MetodeKirim' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'TanggalJawab' => [
                    'nullable',
                    'date',
                ],

                'SaldoJawab' => [
                    'nullable',
                    'integer',
                ],

                'Selisih' => [
                    'nullable',
                    'integer',
                ],

                'Status' => [
                    'sometimes',
                    'in:terbalas,tidak terbalas',
                ],

                'FileBukti' => [
                    'nullable',
                ],

                'NamaFile' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'TipeFile' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Validasi customer untuk UPDATE individual
        |--------------------------------------------------------------------------
        |
        | Method ini tetap menggunakan validasi existing seperti sebelumnya.
        | Swap dilakukan melalui bulkSave(), bukan update() individual.
        |
        */

        if (array_key_exists('KonfirmasiAsetLainID', $validated) && $validated[
                'KonfirmasiAsetLainID'
            ] !== null
        ) {
            $konfirmasiAsetLain =
                $asetLain
                    ->konfirmasiAsetLain()
                    ->whereKey(
                        $validated[
                            'KonfirmasiAsetLainID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasanAsetLain',
                        function ($query) use ($rekap) {
                            $query->where(
                                'RekapBalasanAsetLainID',
                                '!=',
                                $rekap->getKey()
                            );
                        }
                    )
                    ->exists();

            if (!$konfirmasiAsetLain) {
                return response()->json([
                    'message' => 'Konfirmasi aset lain tidak tersedia untuk Aset Lain ini atau sudah digunakan.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */

        $file = $request->file('FileBukti');

        if ($file) {
            $validated['FileBukti'] =
                file_get_contents($file->getRealPath());

            $validated['NamaFile'] =
                $file ->getClientOriginalName();

            $validated['TipeFile'] =
                $file ->getMimeType();
        }

        $rekap->fill($validated);
        $rekap->save();

        return response()->json(
            $rekap
                ->fresh()
                ->load([
                    'asetLain',
                    'konfirmasiAsetLain',
                ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, int $id): JsonResponse {
        $rekap = RekapBalasanAsetLain::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $rekap
                    ->asetLain
                    ?->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Simpan Aset Lain sebelum delete
        |--------------------------------------------------------------------------
        */

        $asetLain = $rekap->asetLain;
        $rekap->delete();

        $this->rekapCheck($asetLain);

        return response()->json([
            'message' => 'Rekap balasan deleted successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP CHECK
    |--------------------------------------------------------------------------
    */

    private function rekapCheck(?AsetLain $asetLain): void {
        if ($asetLain) {
            $asetLain->updateQuietly([
                'RekapCheck' =>
                    $asetLain
                        ->rekapBalasan()
                        ->exists(),
            ]);
        }
    }
}