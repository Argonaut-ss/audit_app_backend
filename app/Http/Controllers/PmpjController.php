<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Pmpj;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PmpjController extends Controller
{
    public function show(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->with(['kasus.client', 'identifikasi'])
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();
        $pmpj = Pmpj::where('JwbKasusID', $jwbKasusId)->first();

        $defaultPerusahaan = $jwbKasus?->kasus?->client;
        $identifikasi = $jwbKasus->identifikasi;

        if (! $pmpj) {
            $pmpj = new Pmpj([
                'JwbKasusID' => $jwbKasusId,
                'Nama' => $identifikasi?->KontakNama,
                'Jabatan' => $identifikasi?->KontakJabatan,
                'Alamat' => $defaultPerusahaan?->AlamatClient,
                'BeneficialOwner' => $identifikasi?->KontakNama,
                'NamaPerusahaan' => $defaultPerusahaan?->NamaClient ?? $defaultPerusahaan?->NamaKantor ?? null,
                'AlamatPerusahaan' => $defaultPerusahaan?->AlamatKantor ?? $defaultPerusahaan?->AlamatClient ?? null,
                'TahunPeriode' => $jwbKasus?->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y') : null,
            ]);
        }

        $pmpj->NamaPerusahaan = $defaultPerusahaan?->NamaClient ?? $defaultPerusahaan?->NamaKantor ?? $pmpj->NamaPerusahaan;
        $pmpj->AlamatPerusahaan = $defaultPerusahaan?->AlamatKantor ?? $defaultPerusahaan?->AlamatClient ?? $pmpj->AlamatPerusahaan;
        $pmpj->TahunPeriode = $jwbKasus?->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y') : ($pmpj->TahunPeriode ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'PmpjID' => $pmpj->PmpjID,
                'JwbKasusID' => $pmpj->JwbKasusID,
                'Nama' => $pmpj->Nama,
                'Jabatan' => $pmpj->Jabatan,
                'Alamat' => $pmpj->Alamat,
                'BeneficialOwner' => $pmpj->BeneficialOwner,
                'NamaPerusahaan' => $pmpj->NamaPerusahaan,
                'AlamatPerusahaan' => $pmpj->AlamatPerusahaan,
                'TahunPeriode' => $pmpj->TahunPeriode,
                'NamaFileKTP' => $pmpj->NamaFileKTP,
                'has_file_ktp' => ! is_null($pmpj->FileKTP),
                'KategoriPenggunaJasa' => $pmpj->KategoriPenggunaJasa,
                'KategoriBisnisPenggunaJasa' => $pmpj->KategoriBisnisPenggunaJasa,
                'KategoriDomisiliPenggunaJasa' => $pmpj->KategoriDomisiliPenggunaJasa,
                'KategoriKhususTambahan' => $pmpj->KategoriKhususTambahan,
                'PenggunaJasa' => $defaultPerusahaan?->NamaClient,
                'ProfilPenggunaJasa' => $defaultPerusahaan?->JenisClient,
                'ProfilDomisili' => $defaultPerusahaan?->AlamatClient,
            ],
        ]);
    }

    public function fileKtp(Request $request, int $jwbKasusId)
    {
        $pmpj = Pmpj::where('JwbKasusID', $jwbKasusId)->first();

        if (! $pmpj || is_null($pmpj->FileKTP)) {
            return response()->json([
                'success' => false,
                'message' => 'File KTP tidak ditemukan.',
            ], 404);
        }

        $filename = $pmpj->NamaFileKTP ?: 'ktp-file';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return response(
            $pmpj->FileKTP,
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    public function riskConfig(): JsonResponse
    {
        $profiles = [
            [
                'profile_name' => 'Profil Pengguna Jasa',
                'categories' => [
                    'Direksi, Komisaris dan Pejabat Struktural lainnya pada BUMN/BUMD',
                    'Pejabat yang membidangi sektor minyak, gas, mineral dan batubara',
                    'Korporasi Non UMKM',
                    'BUMN/BUMD',
                    'Pengusaha/Wiraswasta',
                    'Pegawai Swasta',
                    'Profesional dan Konsultan',
                    'Korporasi UMKM',
                    'Pedagang',
                    'Pengurus/Pegawai LSM/organisasi tidak berbadan hukum lainnya',
                    'Pengrajin',
                    'Lain-Lain',
                ],
                'risk_map' => [
                    'Tinggi' => [
                        'Direksi, Komisaris dan Pejabat Struktural lainnya pada BUMN/BUMD',
                        'Pejabat yang membidangi sektor minyak, gas, mineral dan batubara',
                    ],
                    'Menengah' => [
                        'Pengusaha/Wiraswasta',
                        'Pegawai Swasta',
                        'Profesional dan Konsultan',
                        'Korporasi UMKM',
                    ],
                    'Rendah' => [
                        'Pedagang',
                        'Pengurus/Pegawai LSM/organisasi tidak berbadan hukum lainnya',
                        'Pengrajin',
                        'Lain-Lain',
                        'Korporasi Non UMKM',
                        'BUMN/BUMD',
                    ],
                ],
            ],
            [
                'profile_name' => 'Profil Bisnis Pengguna Jasa',
                'categories' => [
                    'Perdagangan Kendaraan Bermotor',
                    'Properti',
                    'Perdagangan Berjangka Komoditi',
                    'Asuransi Jiwa',
                    'Manufaktur',
                    'Perdagangan Barang dan/atau Jasa Lainnya',
                    'Transportasi dan Telekomunikasi',
                    'Hotel dan Pariwisata',
                    'Pertanian, Perkebunan Peternakan & Perikanan',
                    'Lain-Lain',
                ],
                'risk_map' => [
                    'Tinggi' => [
                        'Perdagangan Kendaraan Bermotor',
                        'Properti',
                    ],
                    'Menengah' => [
                        'Perdagangan Berjangka Komoditi',
                        'Asuransi Jiwa',
                        'Manufaktur',
                    ],
                    'Rendah' => [
                        'Perdagangan Barang dan/atau Jasa Lainnya',
                        'Transportasi dan Telekomunikasi',
                        'Hotel dan Pariwisata',
                        'Pertanian, Perkebunan Peternakan & Perikanan',
                        'Lain-Lain',
                    ],
                ],
            ],
            [
                'profile_name' => 'Profil Domisili Pengguna Jasa',
                'categories' => [
                    'DKI Jakarta',
                    'Sumatera Utara',
                    'Jawa Timur',
                    'Jawa Barat',
                    'Papua',
                    'Riau',
                    'Bali',
                    'Daerah lainnya',
                ],
                'risk_map' => [
                    'Tinggi' => [
                        'DKI Jakarta',
                        'Sumatera Utara',
                        'Jawa Timur',
                    ],
                    'Menengah' => [
                        'Jawa Barat',
                        'Papua',
                        'Riau',
                        'Bali',
                    ],
                    'Rendah' => [
                        'Daerah lainnya',
                    ],
                ],
            ],
            [
                'profile_name' => 'Kriteria Khusus / Tambahan',
                'categories' => [
                    'Pengguna jasa atau BO melakukan transaksi dengan pihak dari negara beresiko tinggi sesuai daftar rekomendasi Financial Action Task Force (FATF)',
                    'Tidak ada kriteria lainnya',
                ],
                'risk_map' => [
                    'Tinggi' => [
                        'Pengguna jasa atau BO melakukan transaksi dengan pihak dari negara beresiko tinggi sesuai daftar rekomendasi Financial Action Task Force (FATF)',
                    ],
                    'Rendah' => [
                        'Tidak ada kriteria lainnya',
                    ],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    public function update(Request $request, int $jwbKasusId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'Nama' => 'nullable|string|max:255',
            'Jabatan' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string|max:1000',
            'BeneficialOwner' => 'nullable|string|max:255',
            'NamaPerusahaan' => 'nullable|string|max:255',
            'AlamatPerusahaan' => 'nullable|string|max:1000',
            'ProfilPenggunaJasa' => 'nullable|string|max:255',
            'ProfilDomisili' => 'nullable|string|max:1000',
            'TahunPeriode' => 'nullable|string|max:50',
            'FileKTP' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'KategoriPenggunaJasa' => 'nullable|string|max:255',
            'KategoriBisnisPenggunaJasa' => 'nullable|string|max:255',
            'KategoriDomisiliPenggunaJasa' => 'nullable|string|max:255',
            'KategoriKhususTambahan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $jwbKasus = JwbKasus::forUser($request->user())
            ->with(['kasus.client', 'identifikasi'])
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();
        $client = $jwbKasus->kasus?->client;
        $identifikasi = $jwbKasus->identifikasi;

        if ($request->has('NamaPerusahaan') && $client) {
            $client->NamaClient = $request->input('NamaPerusahaan');
        }

        if ($request->has('AlamatPerusahaan') && $client) {
            $client->AlamatKantor = $request->input('AlamatPerusahaan');
        }

        if ($request->has('ProfilPenggunaJasa') && $client) {
            $client->JenisClient = $request->input('ProfilPenggunaJasa');
        }

        if ($request->has('ProfilDomisili') && $client) {
            $client->AlamatClient = $request->input('ProfilDomisili');
        }

        if ($client && $client->isDirty()) {
            $client->save();
        }

        if ($request->has('TahunPeriode')) {
            $jwbKasus->Periode = \Carbon\Carbon::createFromFormat('Y', $request->input('TahunPeriode'))->toDateString();
            $jwbKasus->save();
        }

        $pmpj = Pmpj::firstOrNew(['JwbKasusID' => $jwbKasusId]);

        if (! $pmpj->exists) {
            $pmpj->Nama = $identifikasi?->KontakNama;
            $pmpj->Jabatan = $identifikasi?->KontakJabatan;
            $pmpj->Alamat = $client?->AlamatClient;
            $pmpj->BeneficialOwner = $identifikasi?->KontakNama;
        }

        $fields = [
            'Nama',
            'Jabatan',
            'Alamat',
            'BeneficialOwner',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $pmpj->$field = $request->input($field);
            }
        }

        if ($request->hasFile('FileKTP')) {
            $file = $request->file('FileKTP');

            $pmpj->FileKTP = file_get_contents($file->getRealPath());
            $pmpj->NamaFileKTP = $file->getClientOriginalName();
        }

        $pmpj->NamaPerusahaan = $client?->NamaClient;
        $pmpj->AlamatPerusahaan = $client?->AlamatKantor ?? $client?->AlamatClient;
        $pmpj->TahunPeriode = $jwbKasus->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y') : null;

        $profileFields = [
            'KategoriPenggunaJasa',
            'KategoriBisnisPenggunaJasa',
            'KategoriDomisiliPenggunaJasa',
            'KategoriKhususTambahan',
        ];

        foreach ($profileFields as $field) {
            if ($request->has($field)) {
                $pmpj->$field = $request->input($field);
            }
        }

        $pmpj->save();

        return response()->json([
            'success' => true,
            'message' => 'Data PMPJ berhasil disimpan.',
            'data' => [
                'PmpjID' => $pmpj->PmpjID,
                'JwbKasusID' => $pmpj->JwbKasusID,
                'Nama' => $pmpj->Nama,
                'Jabatan' => $pmpj->Jabatan,
                'Alamat' => $pmpj->Alamat,
                'BeneficialOwner' => $pmpj->BeneficialOwner,
                'NamaPerusahaan' => $pmpj->NamaPerusahaan,
                'AlamatPerusahaan' => $pmpj->AlamatPerusahaan,
                'TahunPeriode' => $pmpj->TahunPeriode,
                'NamaFileKTP' => $pmpj->NamaFileKTP,
                'has_file_ktp' => ! is_null($pmpj->FileKTP),
                'KategoriPenggunaJasa' => $pmpj->KategoriPenggunaJasa,
                'KategoriBisnisPenggunaJasa' => $pmpj->KategoriBisnisPenggunaJasa,
                'KategoriDomisiliPenggunaJasa' => $pmpj->KategoriDomisiliPenggunaJasa,
                'KategoriKhususTambahan' => $pmpj->KategoriKhususTambahan,
                'PenggunaJasa' => $client?->NamaClient,
                'ProfilPenggunaJasa' => $client?->JenisClient,
                'ProfilDomisili' => $client?->AlamatClient,
            ],
        ]);
    }
}
