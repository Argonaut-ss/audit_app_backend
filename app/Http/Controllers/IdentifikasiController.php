<?php

namespace App\Http\Controllers;

use App\Models\Identifikasi;
use App\Models\JwbKasus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentifikasiController extends Controller
{
    public function show(
        Request $request,
        int $jwbKasusId
    ): JsonResponse {

        $jwbKasus = JwbKasus::forUser($request->user())
            ->with([
                'kasus.client',
                'identifikasi',
            ])
            ->where(
                'JwbKasusID',
                $jwbKasusId
            )
            ->first();

        if (!$jwbKasus) {
            return response()->json([
                'message' =>
                    'Data kasus tidak ditemukan.',
            ], 404);
        }

        $client = $jwbKasus->kasus?->client;
        $identifikasi = $jwbKasus->identifikasi;

        return response()->json([
            'success' => true,

            'data' => [
                'profil_klien' => [
                    // DataClient
                    'NamaKlien' =>
                        $client?->NamaClient,

                    'NoTelp' =>
                        $client?->HPClient,

                    'AlamatKlien' =>
                        $client?->AlamatClient,

                    'NPWP' =>
                        $client?->NPWP,

                    // JwbKasus
                    'SektorUsaha' =>
                        $jwbKasus->JenisPerusahaan,

                    'TahunBukuDiAudit' =>
                        $this->getTahunBuku(
                            $jwbKasus->Periode
                        ),

                    'WaktuPeriode' =>
                        $jwbKasus->Periode,

                    'WaktuMulai' =>
                        $jwbKasus->WaktuMulai,

                    'BatasWaktu' =>
                        $jwbKasus->BatasWaktu,
                ],

                // Identifikasi
                'detail_identifikasi' =>
                    $this->identifikasiResponse(
                        $identifikasi
                    ),
            ],
        ]);
    }

    public function update(
        Request $request,
        int $jwbKasusId
    ): JsonResponse {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->with([
                'kasus.client',
                'identifikasi',
            ])
            ->where(
                'JwbKasusID',
                $jwbKasusId
            )
            ->first();

        if (!$jwbKasus) {
            return response()->json([
                'message' =>
                    'Data kasus tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        $user = $request->user();

        $isOwner =
            $user->isMahasiswa()
            && $user->mahasiswa
            && $jwbKasus->MahasiswasID === $user->mahasiswa->id;

        $canManage =
            $user->isAdmin()
            || $user->isDosen();

        abort_if(
            !$canManage && !$isOwner,
            403,
            'Anda tidak memiliki akses untuk mengubah data ini.'
        );

        $validated = $request->validate([

            /*
             * ================================================
             * PROFIL KLIEN
             * ================================================
             *
             * NamaKlien   → DataClient.NamaClient
             * NoTelp     → DataClient.HPClient
             * AlamatKlien→ DataClient.AlamatClient
             * NPWP       → DataClient.NPWP
             *
             * SektorUsaha → JwbKasus.JenisPerusahaan
             * WaktuPeriode → JwbKasus.Periode
             * WaktuMulai   → JwbKasus.WaktuMulai
             * BatasWaktu   → JwbKasus.BatasWaktu
             */

            'NamaKlien' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'NoTelp' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'AlamatKlien' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'NPWP' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'SektorUsaha' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'WaktuPeriode' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'WaktuMulai' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'BatasWaktu' => [
                'sometimes',
                'nullable',
                'date',
            ],

            // Detail Identifikasi
            'Tahun' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1900',
                'max:2100',
            ],

            'OpiniAudit' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'NoSuratPengesahan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'LaporanSPT' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'NoSuratKeputusan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'LaporanKeuangan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'TipePerikatan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'SumberDana' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'JenisPerikatan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'TujuanTransaksi' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'StandardAkutansi' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'TotalAset' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'NamaKAP' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'Pendapatan' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'LabaRugi' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            'KontakNama' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'KontakJabatan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'KontakNomor' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'KontakEmail' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            // File Blob
            'FileAkte' => [
                'sometimes',
                'nullable',
                'file',
                'max:10240',
            ],

            'FileNPWP' => [
                'sometimes',
                'nullable',
                'file',
                'max:10240',
            ],

            'FileStrukturOrg' => [
                'sometimes',
                'nullable',
                'file',
                'max:10240',
            ],
        ]);

        // DB Transaction
        DB::transaction(function () use (
            $validated,
            $request,
            $jwbKasus
        ) {

            // DataClient
            $client = $jwbKasus->kasus?->client;

            if ($client) {

                $clientData = [];

                if (array_key_exists(
                    'NamaKlien',
                    $validated
                )) {
                    $clientData['NamaClient'] =
                        $validated['NamaKlien'];
                }

                if (array_key_exists(
                    'NoTelp',
                    $validated
                )) {
                    $clientData['HPClient'] =
                        $validated['NoTelp'];
                }

                if (array_key_exists(
                    'AlamatKlien',
                    $validated
                )) {
                    $clientData['AlamatClient'] =
                        $validated['AlamatKlien'];
                }

                if (array_key_exists(
                    'NPWP',
                    $validated
                )) {
                    $clientData['NPWP'] =
                        $validated['NPWP'];
                }

                if (!empty($clientData)) {
                    $client->update($clientData);
                }
            }

            // JwbKasus
            $jwbKasusData = [];

            if (array_key_exists(
                'SektorUsaha',
                $validated
            )) {
                $jwbKasusData['JenisPerusahaan'] =
                    $validated['SektorUsaha'];
            }

            if (array_key_exists(
                'WaktuPeriode',
                $validated
            )) {
                $jwbKasusData['Periode'] =
                    $validated['WaktuPeriode'];
            }

            if (array_key_exists(
                'WaktuMulai',
                $validated
            )) {
                $jwbKasusData['WaktuMulai'] =
                    $validated['WaktuMulai'];
            }

            if (array_key_exists(
                'BatasWaktu',
                $validated
            )) {
                $jwbKasusData['BatasWaktu'] =
                    $validated['BatasWaktu'];
            }

            if (!empty($jwbKasusData)) {
                $jwbKasus->update($jwbKasusData);
            }
            
            // Identifikasi
            $identifikasi =
                $jwbKasus->identifikasi;

            if (!$identifikasi) {
                $identifikasi =
                    new Identifikasi();

                $identifikasi->JwbKasusID =
                    $jwbKasus->JwbKasusID;
            }


            $identifikasiFields = [
                'Tahun',
                'OpiniAudit',
                'NoSuratPengesahan',
                'LaporanSPT',
                'NoSuratKeputusan',
                'LaporanKeuangan',
                'TipePerikatan',
                'SumberDana',
                'JenisPerikatan',
                'TujuanTransaksi',
                'StandardAkutansi',
                'TotalAset',
                'NamaKAP',
                'Pendapatan',
                'LabaRugi',
                'KontakNama',
                'KontakJabatan',
                'KontakNomor',
                'KontakEmail',
            ];

            foreach ($identifikasiFields as $field) {
                if (array_key_exists(
                    $field,
                    $validated
                )) {
                    $identifikasi->{$field} =
                        $validated[$field];
                }
            }

            // File Blob
            $fileFields = [
                'FileAkte',
                'FileNPWP',
                'FileStrukturOrg',
            ];

            foreach ($fileFields as $field) {

                if ($request->hasFile($field)) {

                    $file =
                        $request->file($field);

                    $identifikasi->{$field} =
                        file_get_contents(
                            $file->getRealPath()
                        );
                }
            }


            $identifikasi->save();
        });

        // Return updated form data
        $jwbKasus->load([
            'kasus.client',
            'identifikasi',
        ]);

        $client =
            $jwbKasus->kasus?->client;

        return response()->json([
            'success' => true,

            'message' =>
                'Data identifikasi berhasil diperbarui.',

            'data' => [
                'profil_klien' => [
                    'NamaKlien' =>
                        $client?->NamaClient,

                    'NoTelp' =>
                        $client?->HPClient,

                    'AlamatKlien' =>
                        $client?->AlamatClient,

                    'NPWP' =>
                        $client?->NPWP,

                    'SektorUsaha' =>
                        $jwbKasus->JenisPerusahaan,

                    'TahunBukuDiAudit' =>
                        $this->getTahunBuku(
                            $jwbKasus->Periode
                        ),

                    'WaktuPeriode' =>
                        $jwbKasus->Periode,

                    'WaktuMulai' =>
                        $jwbKasus->WaktuMulai,

                    'BatasWaktu' =>
                        $jwbKasus->BatasWaktu,
                ],

                'detail_identifikasi' =>
                    $this->identifikasiResponse(
                        $jwbKasus->identifikasi
                    ),
            ],
        ]);
    }

    private function identifikasiResponse(
        ?Identifikasi $identifikasi
    ): ?array {

        if (!$identifikasi) {
            return null;
        }

        return [
            'IdentifikasiID' =>
                $identifikasi->IdentifikasiID,

            'JwbKasusID' =>
                $identifikasi->JwbKasusID,

            'Tahun' =>
                $identifikasi->Tahun,

            'OpiniAudit' =>
                $identifikasi->OpiniAudit,

            'NoSuratPengesahan' =>
                $identifikasi->NoSuratPengesahan,

            'LaporanSPT' =>
                $identifikasi->LaporanSPT,

            'NoSuratKeputusan' =>
                $identifikasi->NoSuratKeputusan,

            'LaporanKeuangan' =>
                $identifikasi->LaporanKeuangan,

            'TipePerikatan' =>
                $identifikasi->TipePerikatan,

            'SumberDana' =>
                $identifikasi->SumberDana,

            'JenisPerikatan' =>
                $identifikasi->JenisPerikatan,

            'TujuanTransaksi' =>
                $identifikasi->TujuanTransaksi,

            'StandardAkutansi' =>
                $identifikasi->StandardAkutansi,

            'TotalAset' =>
                $identifikasi->TotalAset,

            'NamaKAP' =>
                $identifikasi->NamaKAP,

            'Pendapatan' =>
                $identifikasi->Pendapatan,

            'LabaRugi' =>
                $identifikasi->LabaRugi,

            'KontakNama' =>
                $identifikasi->KontakNama,

            'KontakJabatan' =>
                $identifikasi->KontakJabatan,

            'KontakNomor' =>
                $identifikasi->KontakNomor,

            'KontakEmail' =>
                $identifikasi->KontakEmail,

            'has_file_akte' =>
                !is_null(
                    $identifikasi->FileAkte
                ),

            'has_file_npwp' =>
                !is_null(
                    $identifikasi->FileNPWP
                ),

            'has_file_struktur_org' =>
                !is_null(
                    $identifikasi->FileStrukturOrg
                ),
        ];
    }

    // Ambil fieled Tahun Buku dari tahun di WaktuPeriode dalam JwbKasus
    private function getTahunBuku(
        $periode
    ): ?int {

        if (!$periode) {
            return null;
        }

        try {
            return Carbon::parse($periode)->year;
        } catch (\Throwable $e) {
            return null;
        }
    }
}