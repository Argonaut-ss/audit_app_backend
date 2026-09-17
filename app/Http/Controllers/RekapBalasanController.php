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
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

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
            $usedKonfirmasiPiutangIds = $piutang
                ->rekapBalasan
                ->pluck('KonfirmasiPiutangID')
                ->filter();

            $piutang->setRelation(
                'konfirmasiPiutangTersedia',
                $piutang
                    ->konfirmasiPiutang
                    ->whereNotIn(
                        'KonfirmasiPiutangID',
                        $usedKonfirmasiPiutangIds
                    )
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

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $data = RekapBalasan::with([
            'piutang',
            'konfirmasiPiutang',
        ])->findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $data->piutang?->JwbKasusID
            )
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
        $item = RekapBalasan::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $item->piutang?->JwbKasusID
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

    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'PiutangID' => [
                'required',
                'exists:Piutang,PiutangID',
            ],

            'KonfirmasiPiutangID' => [
                'nullable',
                'exists:KonfirmasiPiutang,KonfirmasiPiutangID',
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

        $piutang = Piutang::findOrFail(
            $validated['PiutangID']
        );

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $piutang->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Pastikan customer masih tersedia
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $validated['KonfirmasiPiutangID']
            )
        ) {
            $konfirmasiPiutang =
                $piutang
                    ->konfirmasiPiutang()
                    ->whereKey(
                        $validated[
                            'KonfirmasiPiutangID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasan'
                    )
                    ->exists();

            if (!$konfirmasiPiutang) {
                return response()->json([
                    'message' =>
                        'Konfirmasi piutang tidak tersedia untuk Piutang ini atau sudah digunakan.',
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
                file_get_contents(
                    $file->getRealPath()
                );

            $validated['NamaFile'] =
                $file->getClientOriginalName();

            $validated['TipeFile'] =
                $file->getMimeType();
        }

        $rekap = RekapBalasan::create(
            $validated
        );

        $this->rekapCheck(
            $piutang
        );

        return response()->json(
            $rekap->load([
                'piutang',
                'konfirmasiPiutang',
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

    public function bulkSave(
        Request $request
    ): JsonResponse {
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

        $piutangIds = $rows
            ->pluck('PiutangID')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        foreach ($piutangIds as $piutangId) {
            $piutang = Piutang::findOrFail(
                $piutangId
            );

            JwbKasus::forUser(
                $request->user()
            )
                ->where(
                    'JwbKasusID',
                    $piutang->JwbKasusID
                )
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CEK DUPLICATE RekapBalasanID DALAM PAYLOAD
        |--------------------------------------------------------------------------
        |
        | ID existing yang sama tidak boleh dikirim dua kali.
        |
        */

        $submittedExistingIds = $rows
            ->pluck('RekapBalasanID')
            ->filter(
                fn ($id) =>
                    !is_null($id)
                    && $id !== ''
            )
            ->map(
                fn ($id) => (int) $id
            );

        if (
            $submittedExistingIds->count()
            !==
            $submittedExistingIds
                ->unique()
                ->count()
        ) {
            throw ValidationException::withMessages([
                'data' =>
                    'RekapBalasanID yang sama tidak boleh dikirim lebih dari satu kali.',
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
                            'KonfirmasiPiutangID'
                        ] ?? null
                    )
            )
            ->map(
                fn ($row) =>
                    (int) $row['PiutangID']
                    . ':'
                    . (int) $row[
                        'KonfirmasiPiutangID'
                    ]
            );

        if (
            $konfirmasiKeys->count()
            !==
            $konfirmasiKeys
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
        | 4. PASTIKAN CUSTOMER MEMANG MILIK PIUTANG TERSEBUT
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $konfirmasiId =
                $row[
                    'KonfirmasiPiutangID'
                ] ?? null;

            if (
                is_null(
                    $konfirmasiId
                )
            ) {
                continue;
            }

            $exists = Piutang::where(
                'PiutangID',
                $row['PiutangID']
            )
                ->whereHas(
                    'konfirmasiPiutang',
                    function (
                        $query
                    ) use (
                        $konfirmasiId
                    ) {
                        $query->where(
                            'KonfirmasiPiutangID',
                            $konfirmasiId
                        );
                    }
                )
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
        | 5. EXISTING REKAP HARUS MILIK PIUTANG YANG DIKIRIM
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $rekapId =
                $row[
                    'RekapBalasanID'
                ] ?? null;

            if (
                is_null(
                    $rekapId
                )
            ) {
                continue;
            }

            $belongsToPiutang =
                RekapBalasan::where(
                    'RekapBalasanID',
                    $rekapId
                )
                    ->where(
                        'PiutangID',
                        $row['PiutangID']
                    )
                    ->exists();

            if (!$belongsToPiutang) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Rekap balasan tidak sesuai dengan Piutang yang dipilih.',
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
        |   ->where('KonfirmasiPiutangID', target)
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
                $piutangIds
            ) {
                /*
                |--------------------------------------------------------------------------
                | Diproses PER PIUTANG
                |--------------------------------------------------------------------------
                */

                foreach (
                    $piutangIds
                    as
                    $piutangId
                ) {
                    $rowsForPiutang =
                        $rows
                            ->filter(
                                fn ($row) =>
                                    (int)
                                    $row[
                                        'PiutangID'
                                    ]
                                    ===
                                    (int)
                                    $piutangId
                            )
                            ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID YANG DIKIRIM FE
                    |--------------------------------------------------------------------------
                    */

                    $submittedRekapIds =
                        $rowsForPiutang
                            ->pluck(
                                'RekapBalasanID'
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
                        RekapBalasan::where(
                            'PiutangID',
                            $piutangId
                        )
                            ->pluck(
                                'RekapBalasanID'
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

                    if (
                        $idsToDelete
                            ->isNotEmpty()
                    ) {
                        RekapBalasan::whereIn(
                            'RekapBalasanID',
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

                    if (
                        $submittedRekapIds
                            ->isNotEmpty()
                    ) {
                        RekapBalasan::where(
                            'PiutangID',
                            $piutangId
                        )
                            ->whereIn(
                                'RekapBalasanID',
                                $submittedRekapIds
                            )
                            ->update([
                                'KonfirmasiPiutangID'
                                    => null,
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE / CREATE FINAL STATE
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $rowsForPiutang
                        as
                        $row
                    ) {
                        $rekapId =
                            $row[
                                'RekapBalasanID'
                            ] ?? null;

                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE EXISTING
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !is_null(
                                $rekapId
                            )
                        ) {
                            $rekap =
                                RekapBalasan::where(
                                    'RekapBalasanID',
                                    $rekapId
                                )
                                    ->where(
                                        'PiutangID',
                                        $piutangId
                                    )
                                    ->firstOrFail();

                            $updateData = [
                                'KonfirmasiPiutangID' =>
                                    $row[
                                        'KonfirmasiPiutangID'
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

                            if (
                                isset(
                                    $row[
                                        'FileBukti'
                                    ]
                                )
                            ) {
                                $file =
                                    $row[
                                        'FileBukti'
                                    ];

                                $updateData[
                                    'FileBukti'
                                ] =
                                    file_get_contents(
                                        $file
                                            ->getRealPath()
                                    );

                                $updateData[
                                    'NamaFile'
                                ] =
                                    $file
                                        ->getClientOriginalName();

                                $updateData[
                                    'TipeFile'
                                ] =
                                    $file
                                        ->getMimeType();
                            }

                            $rekap->fill(
                                $updateData
                            );

                            $rekap->save();
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE NEW
                        |--------------------------------------------------------------------------
                        */

                        else {
                            $createData = [
                                'PiutangID' =>
                                    $piutangId,

                                'KonfirmasiPiutangID' =>
                                    $row[
                                        'KonfirmasiPiutangID'
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

                            if (
                                isset(
                                    $row[
                                        'FileBukti'
                                    ]
                                )
                            ) {
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

                            RekapBalasan::create(
                                $createData
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE REKAP CHECK
                    |--------------------------------------------------------------------------
                    */

                    $piutang =
                        Piutang::find(
                            $piutangId
                        );

                    if ($piutang) {
                        $piutang->updateQuietly([
                            'RekapCheck' =>
                                $piutang
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
            'message' =>
                'Data rekap balasan berhasil disimpan.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $rekap =
            RekapBalasan::findOrFail(
                $id
            );

        $piutang =
            $rekap->piutang;

        JwbKasus::forUser(
            $request->user()
        )
            ->where(
                'JwbKasusID',
                $piutang?->JwbKasusID
            )
            ->firstOrFail();

        $validated =
            $request->validate([
                'KonfirmasiPiutangID' => [
                    'nullable',
                    'exists:KonfirmasiPiutang,KonfirmasiPiutangID',
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

        if (
            array_key_exists(
                'KonfirmasiPiutangID',
                $validated
            )
            &&
            $validated[
                'KonfirmasiPiutangID'
            ] !== null
        ) {
            $konfirmasiPiutang =
                $piutang
                    ->konfirmasiPiutang()
                    ->whereKey(
                        $validated[
                            'KonfirmasiPiutangID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasan',
                        function (
                            $query
                        ) use (
                            $rekap
                        ) {
                            $query->where(
                                'RekapBalasanID',
                                '!=',
                                $rekap->getKey()
                            );
                        }
                    )
                    ->exists();

            if (!$konfirmasiPiutang) {
                return response()->json([
                    'message' =>
                        'Konfirmasi piutang tidak tersedia untuk Piutang ini atau sudah digunakan.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */

        $file =
            $request->file(
                'FileBukti'
            );

        if ($file) {
            $validated['FileBukti'] =
                file_get_contents(
                    $file->getRealPath()
                );

            $validated['NamaFile'] =
                $file
                    ->getClientOriginalName();

            $validated['TipeFile'] =
                $file
                    ->getMimeType();
        }

        $rekap->fill(
            $validated
        );

        $rekap->save();

        return response()->json(
            $rekap
                ->fresh()
                ->load([
                    'piutang',
                    'konfirmasiPiutang',
                ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        int $id
    ): JsonResponse {
        $rekap =
            RekapBalasan::findOrFail(
                $id
            );

        JwbKasus::forUser(
            $request->user()
        )
            ->where(
                'JwbKasusID',
                $rekap
                    ->piutang
                    ?->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Simpan Piutang sebelum delete
        |--------------------------------------------------------------------------
        */

        $piutang =
            $rekap->piutang;

        $rekap->delete();

        $this->rekapCheck(
            $piutang
        );

        return response()->json([
            'message' =>
                'Rekap balasan deleted successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP CHECK
    |--------------------------------------------------------------------------
    */

    private function rekapCheck(
        ?Piutang $piutang
    ): void {
        if ($piutang) {
            $piutang->updateQuietly([
                'RekapCheck' =>
                    $piutang
                        ->rekapBalasan()
                        ->exists(),
            ]);
        }
    }
}