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
                'TipeKelas',
                'NamaTugas',
                'NamaFile',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'KasusID' => $item->KasusID,
                    'KelasID' => $item->KelasID,
                    'TipeKelas' => $item->TipeKelas,
                    'NamaTugas' => $item->NamaTugas,
                    'NamaFile' => $item->NamaFile,
                    'NamaKelas' => $item->KelasID,
                ];
            });

        return response()->json($kasus);
    }

    /**
     * Membuat tugas baru.
     *
     * Satu kode kelas + satu tipe kelas
     * hanya boleh mempunyai satu tugas.
     *
     * Contoh:
     *
     * LA01 + UTS
     * LA01 + UAS
     * LA01 + Tugas
     *
     * Ketiganya boleh ada.
     *
     * Tetapi:
     *
     * LA01 + UTS
     * LA01 + UTS
     *
     * tidak boleh ada dua.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'KelasID' => [
                'required',
                'string',
                'exists:kelas,kode_kelas',
            ],

            'TipeKelas' => [
                'required',
                'string',
                'in:UTS,UAS,Tugas,Sandbox',
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
         * Satu KelasID + TipeKelas
         * hanya boleh mempunyai satu tugas.
         *
         * Contoh:
         *
         * LA01 + UTS   -> boleh 1
         * LA01 + UAS   -> boleh 1
         * LA01 + Tugas -> boleh 1
         *
         * LA01 + UTS kedua -> ditolak.
         *
         * Hari dan jam pada tabel kelas
         * TIDAK ikut diperiksa.
         *
         * Jadi:
         *
         * LA01 Senin 07.00
         * LA01 Rabu 09.00
         *
         * tetap menggunakan tugas yang sama:
         *
         * LA01 + UTS
         */
        $existing = Kasus::where(
            'KelasID',
            $validated['KelasID']
        )
        ->where(
            'TipeKelas',
            $validated['TipeKelas']
        )
        ->exists();

        if ($existing) {
            return response()->json([
                'message' =>
                    'Kelas ' .
                    $validated['KelasID'] .
                    ' sudah memiliki tugas untuk tipe kelas ' .
                    $validated['TipeKelas'] .
                    '.',
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
         * Simpan tugas.
         *
         * TipeKelas DIAMBIL DARI FRONTEND,
         * bukan dari tabel kelas.
         */
        $kasus = Kasus::create([
            'KelasID' => $validated['KelasID'],
            'TipeKelas' => $validated['TipeKelas'],
            'NamaTugas' => $validated['NamaTugas'],
            'NamaFile' => $uploadedFile->getClientOriginalName(),
            'File' => $fileContent,
        ]);

        /*
         * Jangan mengembalikan File karena
         * File berisi binary PDF.
         */
        return response()->json([
            'message' => 'Tugas berhasil dibuat.',

            'data' => [
                'KasusID' => $kasus->KasusID,
                'KelasID' => $kasus->KelasID,
                'TipeKelas' => $kasus->TipeKelas,
                'NamaTugas' => $kasus->NamaTugas,
                'NamaFile' => $kasus->NamaFile,
                'NamaKelas' => $kasus->KelasID,
            ],
        ], 201);
    }

    /**
     * Menampilkan file PDF.
     */
    public function file($id)
    {
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
