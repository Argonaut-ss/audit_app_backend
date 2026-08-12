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
                'Client',
                'NamaTugas',
                'NamaFile',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'KasusID' => $item->KasusID,
                    'KelasID' => $item->KelasID,
                    'TipeKelas' => $item->TipeKelas,
                    'Client' => $item->Client,
                    'NamaTugas' => $item->NamaTugas,
                    'NamaFile' => $item->NamaFile,
                    'NamaKelas' => $item->KelasID,
                ];
            });

        return response()->json($kasus);
    }

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

            'Client' => [
                'required',
                'string',
                'max:255',
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
         * =====================================================
         * CEK DUPLIKASI KASUS
         * =====================================================
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

        $uploadedFile = $request->file('file');

        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );

        $kasus = Kasus::create([
            'KelasID' => $validated['KelasID'],
            'TipeKelas' => $validated['TipeKelas'],
            'Client' => $validated['Client'],
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
                'Client' => $kasus->Client,
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