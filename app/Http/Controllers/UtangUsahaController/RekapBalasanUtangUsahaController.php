<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\UtangUsaha\UtangUsaha;
use App\Models\UtangUsaha\RekapBalasanUtangUsaha;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekapBalasanUtangUsahaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $query = UtangUsaha::query()->with([
            'konfirmasiUtangUsaha:KonfirmasiUtangUsahaID,UtangUsahaID,NamaCustomer,Jumlah',
            'rekapBalasan.konfirmasiUtangUsaha:KonfirmasiUtangUsahaID,UtangUsahaID,NamaCustomer,Jumlah',
        ]);

        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });

        $items = $query->get();

        $items->each(function (UtangUsaha $utangUsaha) {
            $usedKonfirmasiUtangUsahaIds = $utangUsaha
                ->rekapBalasan
                ->pluck('KonfirmasiUtangUsahaID')
                ->filter();

            $utangUsaha->setRelation(
                'konfirmasiUtangUsahaTersedia',
                $utangUsaha
                    ->konfirmasiUtangUsaha
                    ->whereNotIn(
                        'KonfirmasiUtangUsahaID',
                        $usedKonfirmasiUtangUsahaIds
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
        $data = RekapBalasanUtangUsaha::with([
            'utangUsaha',
            'konfirmasiUtangUsaha',
        ])->findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $data->utangUsaha?->JwbKasusID
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
        $item = RekapBalasanUtangUsaha::findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $item->utangUsaha?->JwbKasusID
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
            'UtangUsahaID' => [
                'required',
                'exists:UtangUsaha,UtangUsahaID',
            ],

            'KonfirmasiUtangUsahaID' => [
                'nullable',
                'exists:KonfirmasiUtangUsaha,KonfirmasiUtangUsahaID',
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

        $utangUsaha = UtangUsaha::findOrFail(
            $validated['UtangUsahaID']
        );

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $utangUsaha->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Pastikan customer masih tersedia
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $validated['KonfirmasiUtangUsahaID']
            )
        ) {
            $konfirmasiUtangUsaha =
                $utangUsaha
                    ->konfirmasiUtangUsaha()
                    ->whereKey(
                        $validated[
                            'KonfirmasiUtangUsahaID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasanUtangUsaha'
                    )
                    ->exists();

            if (!$konfirmasiUtangUsaha) {
                return response()->json([
                    'message' =>
                        'Konfirmasi utang usaha tidak tersedia untuk Utang Usaha ini atau sudah digunakan.',
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

        $rekap = RekapBalasanUtangUsaha::create(
            $validated
        );

        $this->rekapCheck(
            $utangUsaha
        );

        return response()->json(
            $rekap->load([
                'utangUsaha',
                'konfirmasiUtangUsaha',
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

            'data.*.RekapBalasanUtangUsahaID' => [
                'nullable',
                'integer',
                'exists:RekapBalasanUtangUsaha,RekapBalasanUtangUsahaID',
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

        $utangUsahaIds = $rows
            ->pluck('UtangUsahaID')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        foreach ($utangUsahaIds as $utangUsahaId) {
            $utangUsaha = UtangUsaha::findOrFail(
                $utangUsahaId
            );

            JwbKasus::forUser(
                $request->user()
            )
                ->where(
                    'JwbKasusID',
                    $utangUsaha->JwbKasusID
                )
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CEK DUPLICATE RekapBalasanUtangUsahaID DALAM PAYLOAD
        |--------------------------------------------------------------------------
        |
        | ID existing yang sama tidak boleh dikirim dua kali.
        |
        */

        $submittedExistingIds = $rows
            ->pluck('RekapBalasanUtangUsahaID')
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
                    'RekapBalasanUtangUsahaID yang sama tidak boleh dikirim lebih dari satu kali.',
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
                            'KonfirmasiUtangUsahaID'
                        ] ?? null
                    )
            )
            ->map(
                fn ($row) =>
                    (int) $row['UtangUsahaID']
                    . ':'
                    . (int) $row[
                        'KonfirmasiUtangUsahaID'
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
        | 4. PASTIKAN CUSTOMER MEMANG MILIK UTANG USAHA TERSEBUT
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $konfirmasiId =
                $row[
                    'KonfirmasiUtangUsahaID'
                ] ?? null;

            if (
                is_null(
                    $konfirmasiId
                )
            ) {
                continue;
            }

            $exists = UtangUsaha::where(
                'UtangUsahaID',
                $row['UtangUsahaID']
            )
                ->whereHas(
                    'konfirmasiUtangUsaha',
                    function (
                        $query
                    ) use (
                        $konfirmasiId
                    ) {
                        $query->where(
                            'KonfirmasiUtangUsahaID',
                            $konfirmasiId
                        );
                    }
                )
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Konfirmasi utang usaha tidak sesuai dengan Utang Usaha yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. EXISTING REKAP HARUS MILIK UTANG USAHA YANG DIKIRIM
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $rekapId =
                $row[
                    'RekapBalasanUtangUsahaID'
                ] ?? null;

            if (
                is_null(
                    $rekapId
                )
            ) {
                continue;
            }

            $belongsToUtangUsaha =
                RekapBalasanUtangUsaha::where(
                    'RekapBalasanUtangUsahaID',
                    $rekapId
                )
                    ->where(
                        'UtangUsahaID',
                        $row['UtangUsahaID']
                    )
                    ->exists();

            if (!$belongsToUtangUsaha) {
                throw ValidationException::withMessages([
                    'data' =>
                        'Rekap balasan tidak sesuai dengan Utang Usaha yang dipilih.',
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
        |   ->where('KonfirmasiUtangUsahaID', target)
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
                $utangUsahaIds
            ) {
                /*
                |--------------------------------------------------------------------------
                | Diproses PER UTANG USAHA
                |--------------------------------------------------------------------------
                */

                foreach (
                    $utangUsahaIds
                    as
                    $utangUsahaId
                ) {
                    $rowsForUtangUsaha =
                        $rows
                            ->filter(
                                fn ($row) =>
                                    (int)
                                    $row[
                                        'UtangUsahaID'
                                    ]
                                    ===
                                    (int)
                                    $utangUsahaId
                            )
                            ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID YANG DIKIRIM FE
                    |--------------------------------------------------------------------------
                    */

                    $submittedRekapIds =
                        $rowsForUtangUsaha
                            ->pluck(
                                'RekapBalasanUtangUsahaID'
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
                        RekapBalasanUtangUsaha::where(
                            'UtangUsahaID',
                            $utangUsahaId
                        )
                            ->pluck(
                                'RekapBalasanUtangUsahaID'
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
                        RekapBalasanUtangUsaha::whereIn(
                            'RekapBalasanUtangUsahaID',
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
                        RekapBalasanUtangUsaha::where(
                            'UtangUsahaID',
                            $utangUsahaId
                        )
                            ->whereIn(
                                'RekapBalasanUtangUsahaID',
                                $submittedRekapIds
                            )
                            ->update([
                                'KonfirmasiUtangUsahaID'
                                    => null,
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE / CREATE FINAL STATE
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $rowsForUtangUsaha
                        as
                        $row
                    ) {
                        $rekapId =
                            $row[
                                'RekapBalasanUtangUsahaID'
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
                                RekapBalasanUtangUsaha::where(
                                    'RekapBalasanUtangUsahaID',
                                    $rekapId
                                )
                                    ->where(
                                        'UtangUsahaID',
                                        $utangUsahaId
                                    )
                                    ->firstOrFail();

                            $updateData = [
                                'KonfirmasiUtangUsahaID' =>
                                    $row[
                                        'KonfirmasiUtangUsahaID'
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
                                'UtangUsahaID' =>
                                    $utangUsahaId,

                                'KonfirmasiUtangUsahaID' =>
                                    $row[
                                        'KonfirmasiUtangUsahaID'
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

                    $utangUsaha =
                        UtangUsaha::find(
                            $utangUsahaId
                        );

                    if ($utangUsaha) {
                        $utangUsaha->updateQuietly([
                            'RekapCheck' =>
                                $utangUsaha
                                    ->rekapBalasanUtangUsaha()
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
            RekapBalasanUtangUsaha::findOrFail(
                $id
            );

        $utangUsaha =
            $rekap->utangUsaha;

        JwbKasus::forUser(
            $request->user()
        )
            ->where(
                'JwbKasusID',
                $utangUsaha?->JwbKasusID
            )
            ->firstOrFail();

        $validated =
            $request->validate([
                'KonfirmasiUtangUsahaID' => [
                    'nullable',
                    'exists:KonfirmasiUtangUsaha,KonfirmasiUtangUsahaID',
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
                'KonfirmasiUtangUsahaID',
                $validated
            )
            &&
            $validated[
                'KonfirmasiUtangUsahaID'
            ] !== null
        ) {
            $konfirmasiUtangUsaha =
                $utangUsaha
                    ->konfirmasiUtangUsaha()
                    ->whereKey(
                        $validated[
                            'KonfirmasiUtangUsahaID'
                        ]
                    )
                    ->whereDoesntHave(
                        'rekapBalasanUtangUsaha',
                        function (
                            $query
                        ) use (
                            $rekap
                        ) {
                            $query->where(
                                'RekapBalasanUtangUsahaID',
                                '!=',
                                $rekap->getKey()
                            );
                        }
                    )
                    ->exists();

            if (!$konfirmasiUtangUsaha) {
                return response()->json([
                    'message' =>
                        'Konfirmasi utang usaha tidak tersedia untuk Utang Usaha ini atau sudah digunakan.',
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
                    'utangUsaha',
                    'konfirmasiUtangUsaha',
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
            RekapBalasanUtangUsaha::findOrFail(
                $id
            );

        JwbKasus::forUser(
            $request->user()
        )
            ->where(
                'JwbKasusID',
                $rekap
                    ->utangUsaha
                    ?->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Simpan Utang Usaha sebelum delete
        |--------------------------------------------------------------------------
        */

        $utangUsaha =
            $rekap->utangUsaha;

        $rekap->delete();

        $this->rekapCheck(
            $utangUsaha
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
        ?UtangUsaha $utangUsaha
    ): void {
        if ($utangUsaha) {
            $utangUsaha->updateQuietly([
                'RekapCheck' =>
                    $utangUsaha
                        ->rekapBalasanUtangUsaha()
                        ->exists(),
            ]);
        }
    }
}