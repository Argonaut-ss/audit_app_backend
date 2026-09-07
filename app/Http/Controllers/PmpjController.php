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
        $pmpj = Pmpj::firstOrCreate([
            'JwbKasusID' => $jwbKasusId,
        ]);

        $defaultPerusahaan = $jwbKasus?->kasus?->client;
        $namaPerusahaan = $defaultPerusahaan?->NamaClient ?? $defaultPerusahaan?->NamaKantor;
        $alamatPerusahaan = $defaultPerusahaan?->AlamatClient ?? $defaultPerusahaan?->AlamatKantor;
        $tahunPeriode = $this->getTahunAudit($jwbKasus?->Periode);

        return response()->json([
            'success' => true,
            'data' => [
                'PmpjID' => $pmpj->PmpjID,
                'JwbKasusID' => $pmpj->JwbKasusID,
                'Nama' => $pmpj->Nama,
                'Jabatan' => $pmpj->Jabatan,
                'Alamat' => $pmpj->Alamat,
                'BeneficialOwner' => $pmpj->BeneficialOwner,
                'NamaPerusahaan' => $namaPerusahaan,
                'AlamatPerusahaan' => $alamatPerusahaan,
                'TahunPeriode' => $tahunPeriode,
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
            'TahunPeriode' => 'nullable|string',
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

        if ($request->has('ProfilPenggunaJasa') && $client) {
            $client->JenisClient = $request->input('ProfilPenggunaJasa');
        }

        if ($client && ($request->has('AlamatPerusahaan') || $request->has('ProfilDomisili'))) {
            $alamat = $request->has('AlamatPerusahaan')
                ? $request->input('AlamatPerusahaan')
                : $request->input('ProfilDomisili');
            $client->AlamatClient = $alamat;
            $client->AlamatKantor = $alamat;
        }

        if ($client && $client->isDirty()) {
            $client->save();
        }

        if ($request->filled('TahunPeriode')) {
            $tahun = trim($request->input('TahunPeriode'));
            $jwbKasus->Periode = \Carbon\Carbon::createFromFormat(
                'Y-m-d',
                str_pad($tahun, 4, '0', STR_PAD_LEFT) . '-01-01'
            )->toDateString();
            $jwbKasus->save();
        }

        $pmpj = Pmpj::firstOrNew(['JwbKasusID' => $jwbKasusId]);

        if (! $pmpj->exists) {
            $pmpj->Alamat = null;
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
                'NamaPerusahaan' => $client?->NamaClient ?? $client?->NamaKantor,
                'AlamatPerusahaan' => $client?->AlamatClient ?? $client?->AlamatKantor,
                'TahunPeriode' => $this->getTahunAudit($jwbKasus->Periode),
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

    private function getTahunAudit($periode): ?int
    {
        if (! $periode) {
            return null;
        }

        $year = substr((string) $periode, 0, 4);

        return is_numeric($year) ? (int) $year : null;
    }
}
