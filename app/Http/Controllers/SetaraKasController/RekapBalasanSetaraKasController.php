<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\SetaraKas\SetaraKas;
use App\Models\SetaraKas\RekapBalasanSetaraKas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekapBalasanSetaraKasController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $query = SetaraKas::query()->with([
            'cekSaldoSetaraKas:CekSaldoID,SetaraKasID,NamaBank,SaldoBukuBesar',
            'rekapBalasan.cekSaldoSetaraKas:CekSaldoID,SetaraKasID,NamaBank,SaldoBukuBesar',
        ]);
        $query->whereHas('JwbKasus', function ($query) use ($request) {
            $query->forUser($request->user());
        });
        $items = $query->get();
        $items->each(function (SetaraKas $setaraKas) {
            $usedCekSaldoSetaraKasIds = $setaraKas
                ->rekapBalasan
                ->pluck('CekSaldoSetaraKasID')
                ->filter();

            $setaraKas->setRelation(
                'cekSaldoSetaraKasTersedia',
                $setaraKas
                    ->cekSaldoSetaraKas
                    ->whereNotIn('CekSaldoID', $usedCekSaldoSetaraKasIds)
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
        $data = RekapBalasanSetaraKas::with([
            'setaraKas',
            'cekSaldoSetaraKas',
        ])->findOrFail($id);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $data->setaraKas?->JwbKasusID)
            ->firstOrFail();

        return response()->json($data);
    }

    /*
    |--------------------------------------------------------------------------
    | FILE
    |--------------------------------------------------------------------------
    */

    public function file(Request $request, int $id) {
        $item = RekapBalasanSetaraKas::findOrFail($id);
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID',$item->setaraKas?->JwbKasusID)
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
                'Content-Disposition' => 'attachment; filename="' .addslashes($filename) .'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
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
            'SetaraKasID' => [
                'required',
                'exists:setara_kas,SetaraKasID',
            ],

            'CekSaldoSetaraKasID' => [
                'nullable',
                'exists:cek_saldo_setara_kas,CekSaldoID',
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

            'StatusKonfirmasi' => [
                'nullable',
                'in:diterima,belum diterima',
            ],

            'FileBukti' => [
                'nullable',
                'file',
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

        $setaraKas = SetaraKas::findOrFail($validated['SetaraKasID']);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $setaraKas->JwbKasusID)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Pastikan cek saldo masih tersedia
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['CekSaldoSetaraKasID'])) {
            $cekSaldoSetaraKas = $setaraKas
                ->cekSaldoSetaraKas()
                ->whereKey($validated['CekSaldoSetaraKasID'])
                ->whereDoesntHave('rekapBalasan')
                ->exists();

            if (!$cekSaldoSetaraKas) {
                return response()->json([
                    'message' => 'Cek saldo tidak tersedia untuk Setara Kas ini atau sudah digunakan.',
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
            $validated['FileBukti'] = file_get_contents($file->getRealPath());
            $validated['NamaFile'] = $file->getClientOriginalName();
            $validated['TipeFile'] = $file->getMimeType();
        }

        $rekap = RekapBalasanSetaraKas::create($validated);
        $this->rekapCheck($setaraKas);

        return response()->json(
            $rekap->load([
                'setaraKas',
                'cekSaldoSetaraKas',
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
    | RekapBalasanSetaraKasID ada:
    | -> UPDATE row yang sama.
    |
    | RekapBalasanSetaraKasID tidak ada:
    | -> CREATE row baru.
    |
    | Existing RekapBalasanSetaraKasID tidak dikirim lagi:
    | -> DELETE.
    |
    | SWAP:
    |
    | Sebelum:
    |
    | ID 10 -> Cek Saldo A
    | ID 11 -> Cek Saldo B
    |
    | Payload:
    |
    | ID 10 -> Cek Saldo B
    | ID 11 -> Cek Saldo A
    |
    | Backend:
    |
    | 1. Validasi FINAL STATE.
    | 2. Release cek saldo existing menjadi NULL.
    | 3. Assign cek saldo final.
    |
    | RekapBalasanSetaraKasID tetap 10 dan 11.
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

            'data.*.RekapBalasanSetaraKasID' => [
                'nullable',
                'integer',
                'exists:rekap_balasan_setara_kas,RekapBalasanSetaraKasID',
            ],

            'data.*.SetaraKasID' => [
                'required',
                'integer',
                'exists:setara_kas,SetaraKasID',
            ],

            'data.*.CekSaldoSetaraKasID' => [
                'nullable',
                'integer',
                'exists:cek_saldo_setara_kas,CekSaldoID',
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

            'data.*.StatusKonfirmasi' => [
                'nullable',
                'in:diterima,belum diterima',
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

        $rows = collect($validated['data'])->values();

        /*
        |--------------------------------------------------------------------------
        | 1. AUTHORIZATION
        |--------------------------------------------------------------------------
        */

        $setaraKasIds = $rows
            ->pluck('SetaraKasID')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($setaraKasIds as $setaraKasId) {
            $setaraKas = SetaraKas::findOrFail($setaraKasId);
            JwbKasus::forUser($request->user())
                ->where('JwbKasusID', $setaraKas->JwbKasusID)
                ->firstOrFail();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CEK DUPLICATE RekapBalasanSetaraKasID DALAM PAYLOAD
        |--------------------------------------------------------------------------
        |
        | ID existing yang sama tidak boleh dikirim dua kali.
        |
        */

        $submittedExistingIds = $rows
            ->pluck('RekapBalasanSetaraKasID')
            ->filter(fn ($id) => !is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id);

        if ($submittedExistingIds->count() !== $submittedExistingIds
            ->unique()
            ->count()
        ) {
            throw ValidationException::withMessages(['data' => 'RekapBalasanSetaraKasID yang sama tidak boleh dikirim lebih dari satu kali.',]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. CEK DUPLICATE CEK SALDO PADA FINAL STATE
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

        $cekSaldoKeys = $rows
            ->filter(fn ($row) => !is_null($row['CekSaldoSetaraKasID'] ?? null))
            ->map(fn ($row) => (int) $row['SetaraKasID'] . ':' . (int) $row['CekSaldoSetaraKasID']);

        if ($cekSaldoKeys->count() !== $cekSaldoKeys
            ->unique()
            ->count()
        ) {
            throw ValidationException::withMessages([
                'data' => 'Cek saldo yang sama tidak boleh digunakan pada lebih dari satu baris.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. PASTIKAN CEK SALDO MEMANG MILIK SETARA KAS TERSEBUT
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $cekSaldoId = $row['CekSaldoSetaraKasID'] ?? null;
            if (is_null($cekSaldoId)) {
                continue;
            }

            $exists = SetaraKas::where('SetaraKasID', $row['SetaraKasID'])
                ->whereHas('cekSaldoSetaraKas', function ($query) use ($cekSaldoId) {
                    $query->where('CekSaldoID', $cekSaldoId);
                })->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'data' => 'Cek saldo tidak sesuai dengan Setara Kas yang dipilih.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. EXISTING REKAP HARUS MILIK SETARA KAS YANG DIKIRIM
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {
            $rekapId = $row['RekapBalasanSetaraKasID'] ?? null;

            if (is_null($rekapId)) {
                continue;
            }

            $belongsToSetaraKas = RekapBalasanSetaraKas::where('RekapBalasanSetaraKasID', $rekapId)
                    ->where('SetaraKasID',$row['SetaraKasID'])
                    ->exists();

            if (!$belongsToSetaraKas) {
                throw ValidationException::withMessages([
                    'data' => 'Rekap balasan tidak sesuai dengan Setara Kas yang dipilih.',
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
        | RekapBalasanSetaraKas::where(...)
        |   ->where('CekSaldoSetaraKasID', target)
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
            function () use ($rows, $setaraKasIds) {
                /*
                |--------------------------------------------------------------------------
                | Diproses PER SETARA KAS
                |--------------------------------------------------------------------------
                */

                foreach ($setaraKasIds as $setaraKasId) {
                    $rowsForSetaraKas = $rows
                        ->filter(fn ($row) =>(int) $row['SetaraKasID'] === (int)$setaraKasId)
                        ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID YANG DIKIRIM FE
                    |--------------------------------------------------------------------------
                    */

                    $submittedRekapIds = $rowsForSetaraKas
                        ->pluck('RekapBalasanSetaraKasID')
                        ->filter(fn ($id) => !is_null($id) && $id !== '')
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | EXISTING ID DI DATABASE
                    |--------------------------------------------------------------------------
                    */

                    $existingRekapIds =
                        RekapBalasanSetaraKas::where('SetaraKasID', $setaraKasId)->pluck('RekapBalasanSetaraKasID');

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

                    $idsToDelete = $existingRekapIds->diff($submittedRekapIds);
                    if ($idsToDelete->isNotEmpty()) {
                        RekapBalasanSetaraKas::whereIn(
                            'RekapBalasanSetaraKasID',
                            $idsToDelete
                        )->delete();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RELEASE CEK SALDO EXISTING
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
                    | RekapBalasanSetaraKasID TIDAK berubah.
                    |
                    */

                    if ($submittedRekapIds->isNotEmpty()) {
                        RekapBalasanSetaraKas::where(
                            'SetaraKasID',
                            $setaraKasId
                        )
                        ->whereIn('RekapBalasanSetaraKasID',$submittedRekapIds)
                        ->update(['CekSaldoSetaraKasID' => null,]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE / CREATE FINAL STATE
                    |--------------------------------------------------------------------------
                    */

                    foreach ($rowsForSetaraKas as $row) {
                        $rekapId = $row['RekapBalasanSetaraKasID'] ?? null;

                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE EXISTING
                        |--------------------------------------------------------------------------
                        */

                        if (!is_null($rekapId)) {
                            $rekap = RekapBalasanSetaraKas::where('RekapBalasanSetaraKasID', $rekapId)
                                ->where('SetaraKasID',$setaraKasId)
                                ->firstOrFail();

                            $updateData = [
                                'CekSaldoSetaraKasID' => $row['CekSaldoSetaraKasID'] ?? null,
                                'SaldoBB' => $row['SaldoBB'] ?? null,
                                'TanggalKirim' => $row['TanggalKirim'] ?? null,
                                'MetodeKirim' => $row['MetodeKirim'] ?? null,
                                'TanggalJawab' => $row['TanggalJawab'] ?? null,
                                'SaldoJawab' => $row['SaldoJawab'] ?? null,
                                'Selisih' => $row['Selisih'] ?? null,
                                'StatusKonfirmasi' => $row['StatusKonfirmasi'] ?? null,
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
                                $updateData['FileBukti'] = file_get_contents($file->getRealPath());
                                $updateData['NamaFile'] = $file->getClientOriginalName();
                                $updateData['TipeFile'] = $file->getMimeType();
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
                                'SetaraKasID' =>$setaraKasId,
                                'CekSaldoSetaraKasID' => $row['CekSaldoSetaraKasID'] ?? null,
                                'SaldoBB' => $row['SaldoBB'] ?? null,
                                'TanggalKirim' => $row['TanggalKirim'] ?? null,
                                'MetodeKirim' => $row['MetodeKirim'] ?? null,
                                'TanggalJawab' => $row['TanggalJawab'] ?? null,
                                'SaldoJawab' => $row['SaldoJawab'] ?? null,
                                'Selisih' => $row['Selisih'] ?? null,
                                'StatusKonfirmasi' =>$row['StatusKonfirmasi'] ?? null,
                            ];

                            /*
                            |--------------------------------------------------------------------------
                            | FILE NEW ROW
                            |--------------------------------------------------------------------------
                            */

                            if (isset($row['FileBukti'])) {
                                $file = $row['FileBukti'];
                                $createData['FileBukti'] =
                                    file_get_contents($file->getRealPath());
                                $createData['NamaFile'] =
                                    $file->getClientOriginalName();
                                $createData['TipeFile'] =
                                    $file->getMimeType();
                            }

                            RekapBalasanSetaraKas::create($createData);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE REKAP CHECK
                    |--------------------------------------------------------------------------
                    */

                    $setaraKas = SetaraKas::find($setaraKasId);
                    if ($setaraKas) {
                        $setaraKas->updateQuietly([
                            'RekapCheck' =>
                                $setaraKas
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
        $rekap = RekapBalasanSetaraKas::findOrFail($id);
        $setaraKas = $rekap->setaraKas;

        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $setaraKas?->JwbKasusID
            )
            ->firstOrFail();

        $validated = $request->validate([
            'CekSaldoSetaraKasID' => [
                'nullable',
                'exists:cek_saldo_setara_kas,CekSaldoID',
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
            'StatusKonfirmasi' => [
                'sometimes',
                'in:diterima,belum diterima',
            ],
            'FileBukti' => [
                'nullable',
                'file',
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
        | Validasi cek saldo untuk UPDATE individual
        |--------------------------------------------------------------------------
        |
        | Method ini tetap menggunakan validasi existing seperti sebelumnya.
        | Swap dilakukan melalui bulkSave(), bukan update() individual.
        |
        */

        if (array_key_exists('CekSaldoSetaraKasID', $validated) && $validated['CekSaldoSetaraKasID'] !== null) {
            $cekSaldoSetaraKas = $setaraKas
                ->cekSaldoSetaraKas()
                ->whereKey(
                    $validated[
                        'CekSaldoSetaraKasID'
                    ]
                )
                ->whereDoesntHave(
                    'rekapBalasan',
                    function ($query) use ($rekap) {
                        $query->where(
                            'RekapBalasanSetaraKasID',
                            '!=',
                            $rekap->getKey()
                        );
                    }
                )
                ->exists();

            if (!$cekSaldoSetaraKas) {
                return response()->json([
                    'message' => 'Cek saldo tidak tersedia untuk Setara Kas ini atau sudah digunakan.',
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
                $file->getClientOriginalName();

            $validated['TipeFile'] =
                $file->getMimeType();
        }

        $rekap->fill($validated);
        $rekap->save();

        return response()->json(
            $rekap
                ->fresh()
                ->load([
                    'setaraKas',
                    'cekSaldoSetaraKas',
                ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request,int $id): JsonResponse {
        $rekap = RekapBalasanSetaraKas::findOrFail($id);
        JwbKasus::forUser($request->user())
            ->where(
                'JwbKasusID',
                $rekap->setaraKas ?->JwbKasusID
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Simpan SetaraKas sebelum delete
        |--------------------------------------------------------------------------
        */

        $setaraKas = $rekap->setaraKas;
        $rekap->delete();
        $this->rekapCheck($setaraKas);

        return response()->json([
            'message' => 'Rekap balasan deleted successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP CHECK
    |--------------------------------------------------------------------------
    */

    private function rekapCheck(?SetaraKas $setaraKas): void {
        if ($setaraKas) {
            $setaraKas->updateQuietly([
                'RekapCheck' =>
                    $setaraKas
                        ->rekapBalasan()
                        ->exists(),
            ]);
        }
    }
}