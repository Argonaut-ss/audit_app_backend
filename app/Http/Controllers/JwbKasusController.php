<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Kasus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JwbKasusController extends Controller
{
    /*
     * =====================================================
     * INDEX
     * =====================================================
     *
     * Menampilkan daftar jawaban.
     *
     * File binary tidak ikut dikirim.
     */

    public function index()
    {
        $jawaban = JwbKasus::query()
            ->select([
                'JwbKasusID',
                'SubmisID',
                'KasusID',
                'NIM',
                'TanggalUpload',
                'Nilai',
            ])
            ->with([
                'kasus:KasusID,KelasID,TipeKelas,ClientID,NamaTugas,NamaFile',
                'mahasiswa:NIM',
            ])
            ->orderByDesc('TanggalUpload')
            ->get();

        return response()->json($jawaban);
    }


    /*
     * =====================================================
     * STORE
     * =====================================================
     */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'KasusID' => [
                'required',
                'integer',
                'exists:kasus,KasusID',
            ],

            'NIM' => [
                'required',
                'string',
                'exists:mahasiswa,NIM',
            ],

            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx',
            ],
        ]);


        /*
         * =====================================================
         * AMBIL KASUS
         * =====================================================
         */

        $kasus = Kasus::findOrFail(
            $validated['KasusID']
        );


        /*
         * =====================================================
         * CEK DUPLIKAT
         * =====================================================
         */

        $existing = JwbKasus::where(
            'KasusID',
            $validated['KasusID']
        )
        ->where(
            'NIM',
            $validated['NIM']
        )
        ->exists();


        if ($existing) {

            return response()->json([
                'message' =>
                    'Mahasiswa tersebut sudah mengumpulkan jawaban untuk kasus ini.',
            ], 409);
        }


        /*
         * =====================================================
         * FILE
         * =====================================================
         */

        $uploadedFile = $request->file('file');


        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );


        /*
         * =====================================================
         * SUBMISSION ID
         * =====================================================
         */

        $submisID = 'SUB-' . strtoupper(
            Str::random(12)
        );


        /*
         * =====================================================
         * CREATE
         * =====================================================
         */

        $jawaban = JwbKasus::create([

            'SubmisID' => $submisID,

            'KasusID' => $validated['KasusID'],

            'NIM' => $validated['NIM'],

            'TanggalUpload' => now(),

            'Nilai' => null,

            'File' => $fileContent,
        ]);


        /*
         * =====================================================
         * RESPONSE
         * =====================================================
         */

        return response()->json([
            'message' =>
                'Jawaban kasus berhasil dikumpulkan.',

            'data' => [
                'JwbKasusID' =>
                    $jawaban->JwbKasusID,

                'SubmisID' =>
                    $jawaban->SubmisID,

                'KasusID' =>
                    $jawaban->KasusID,

                'NIM' =>
                    $jawaban->NIM,

                'TanggalUpload' =>
                    $jawaban->TanggalUpload,
            ],
        ], 201);
    }


    /*
     * =====================================================
     * SHOW
     * =====================================================
     */

    public function show($id)
    {
        $jawaban = JwbKasus::query()
            ->select([
                'JwbKasusID',
                'SubmisID',
                'KasusID',
                'NIM',
                'TanggalUpload',
                'Nilai',
            ])
            ->with([
                'kasus:KasusID,KelasID,TipeKelas,ClientID,NamaTugas,NamaFile',
                'mahasiswa:NIM',
            ])
            ->findOrFail($id);


        return response()->json($jawaban);
    }


    /*
     * =====================================================
     * FILE
     * =====================================================
     */

    public function file($id)
    {
        $jawaban = JwbKasus::findOrFail($id);


        if (!$jawaban->File) {

            return response()->json([
                'message' =>
                    'File jawaban tidak ditemukan.',
            ], 404);
        }


        $extension = strtolower(
            pathinfo(
                $jawaban->SubmisID,
                PATHINFO_EXTENSION
            )
        );

        return response(
            $jawaban->File,
            200,
            [
                'Content-Type' =>
                    'application/octet-stream',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $jawaban->SubmisID
                    ) .
                    '"',
            ]
        );
    }


    /*
     * =====================================================
     * UPDATE
     * =====================================================
     */

    public function update(Request $request, $id)
    {
        $jawaban = JwbKasus::findOrFail($id);


        $validated = $request->validate([

            'Nilai' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);


        $jawaban->update([
            'Nilai' =>
                $validated['Nilai'] ?? null,
        ]);


        return response()->json([
            'message' =>
                'Jawaban kasus berhasil diperbarui.',

            'data' => [
                'JwbKasusID' =>
                    $jawaban->JwbKasusID,

                'SubmisID' =>
                    $jawaban->SubmisID,

                'KasusID' =>
                    $jawaban->KasusID,

                'NIM' =>
                    $jawaban->NIM,

                'TanggalUpload' =>
                    $jawaban->TanggalUpload,

                'Nilai' =>
                    $jawaban->Nilai,
            ],
        ]);
    }


    /*
     * =====================================================
     * DESTROY
     * =====================================================
     */

    public function destroy($id)
    {
        $jawaban = JwbKasus::findOrFail($id);


        $jawaban->delete();


        return response()->json([
            'message' =>
                'Jawaban kasus berhasil dihapus.',
        ]);
    }
}