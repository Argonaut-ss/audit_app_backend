<?php

namespace App\Http\Controllers;

use App\Models\Kasus;
use Illuminate\Http\Request;

class KasusController extends Controller
{
    /**
     * Menampilkan semua tugas.
     *
     * File PDF TIDAK diambil agar tidak masuk
     * ke response JSON.
     */
    public function index()
    {
        $kasus = Kasus::query()
            ->select([
                'KasusID',
                'KelasID',
                'NamaTugas',
                'NamaFile',
            ])
            ->with([
                'kelas:kode_kelas,tipe_kelas',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'KasusID'   => $item->KasusID,
                    'KelasID'   => $item->KelasID,
                    'NamaTugas' => $item->NamaTugas,
                    'NamaFile'  => $item->NamaFile,
                    'NamaKelas' => $item->kelas?->kode_kelas,
                    'TipeKelas' => $item->kelas?->tipe_kelas,
                ];
            });

        return response()->json($kasus);
    }

    /**
     * Membuat tugas baru.
     *
     * 1 kelas hanya boleh mempunyai 1 tugas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'KelasID' => [
                'required',
                'string',
                'exists:kelas,kode_kelas',
            ],

            'NamaTugas' => [
                'required',
                'string',
                'max:255',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        /*
         * Cek apakah kelas sudah mempunyai tugas.
         */
        $existing = Kasus::where(
            'KelasID',
            $validated['KelasID']
        )->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Kelas tersebut sudah memiliki tugas.',
            ], 409);
        }

        /*
         * Ambil file PDF.
         */
        $uploadedFile = $request->file('file');

        /*
         * Baca PDF sebagai binary.
         */
        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );

        /*
         * Simpan ke database.
         */
        $kasus = Kasus::create([
            'KelasID'   => $validated['KelasID'],
            'NamaTugas' => $validated['NamaTugas'],
            'NamaFile'  => $uploadedFile->getClientOriginalName(),
            'File'      => $fileContent,
        ]);

        /*
         * Ambil data kelas.
         */
        $kelas = $kasus->kelas;

        /*
         * Jangan mengembalikan kolom File.
         */
        return response()->json([
            'message' => 'Tugas berhasil dibuat.',

            'data' => [
                'KasusID'   => $kasus->KasusID,
                'KelasID'   => $kasus->KelasID,
                'NamaTugas' => $kasus->NamaTugas,
                'NamaFile'  => $kasus->NamaFile,
                'NamaKelas' => $kelas?->kode_kelas,
                'TipeKelas' => $kelas?->tipe_kelas,
            ],
        ], 201);
    }

    /**
     * Menampilkan file PDF.
     */
    public function file($id)
    {
        /*
         * Baru di sini file PDF diambil.
         */
        $kasus = Kasus::findOrFail($id);

        return response(
            $kasus->File,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes($kasus->NamaFile) .
                    '"',
            ]
        );
    }
}