<?php

namespace App\Http\Controllers;

use App\Models\Kasus;
use App\Models\DataClient;
use Illuminate\Http\Request;

class KasusController extends Controller
{
    public function index()
    {
        $kasus = Kasus::query()
            ->select([
                'KasusID',
                'KelasID',
                'TipeKelas',
                'ClientID',
                'NamaTugas',
                'NamaFile',
            ])
            ->with([
                'client:ClientID,NamaClient',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'KasusID' => $item->KasusID,
                    'KelasID' => $item->KelasID,
                    'TipeKelas' => $item->TipeKelas,
                    'ClientID' => $item->ClientID,

                    'NamaClient' => $item->client
                        ? $item->client->NamaClient
                        : null,

                    'NamaTugas' => $item->NamaTugas,
                    'NamaFile' => $item->NamaFile,
                    'NamaKelas' => $item->KelasID,
                ];
            });

        return response()->json($kasus);
    }


    /**
     * =====================================================
     * STORE
     * =====================================================
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

            'NamaClient' => [
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


        /**
         * =================================================
         * CEK DUPLIKASI KASUS
         * =================================================
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


        /**
         * =================================================
         * FILE
         * =================================================
         */

        $uploadedFile = $request->file('file');

        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );


        /**
         * =================================================
         * CARI / BUAT DATA CLIENT
         * =================================================
         */

        $client = DataClient::firstOrCreate(
            [
                'NamaClient' => trim(
                    $validated['NamaClient']
                ),
            ]
        );


        /**
         * =================================================
         * CREATE KASUS
         * =================================================
         */

        $kasus = Kasus::create([

            'KelasID' =>
                $validated['KelasID'],

            'TipeKelas' =>
                $validated['TipeKelas'],

            /*
             * data_client.ClientID
             *          ↓
             * kasus.ClientID
             */
            'ClientID' =>
                $client->ClientID,

            'NamaTugas' =>
                $validated['NamaTugas'],

            'NamaFile' =>
                $uploadedFile->getClientOriginalName(),

            'File' =>
                $fileContent,
        ]);


        /**
         * =================================================
         * RESPONSE
         * =================================================
         */

        return response()->json([

            'message' =>
                'Tugas berhasil dibuat.',

            'data' => [

                'KasusID' =>
                    $kasus->KasusID,

                'KelasID' =>
                    $kasus->KelasID,

                'TipeKelas' =>
                    $kasus->TipeKelas,

                'ClientID' =>
                    $client->ClientID,

                'NamaClient' =>
                    $client->NamaClient,

                'NamaTugas' =>
                    $kasus->NamaTugas,

                'NamaFile' =>
                    $kasus->NamaFile,

                'NamaKelas' =>
                    $kasus->KelasID,
            ],

        ], 201);
    }


    /**
     * =====================================================
     * DESTROY
     * =====================================================
     */

    public function destroy($id)
    {
        $kasus = Kasus::find($id);


        if (!$kasus) {
            return response()->json([
                'message' =>
                    'Tugas tidak ditemukan.',
            ], 404);
        }


        $kasus->delete();


        return response()->json([
            'message' =>
                'Tugas berhasil dihapus.',
        ], 200);
    }


    /**
     * =====================================================
     * FILE
     * =====================================================
     */

    public function file($id)
    {
        $kasus = Kasus::findOrFail($id);


        if (!$kasus->File) {
            return response()->json([
                'message' =>
                    'File tugas tidak ditemukan.',
            ], 404);
        }


        return response(
            $kasus->File,
            200,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $kasus->NamaFile
                    ) .
                    '"',
            ]
        );
    }
}