<?php

namespace App\Http\Controllers;

use App\Models\Kasus;
use App\Models\DataClient;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasusController extends Controller
{
    /**
     * =====================================================
     * INDEX
     * =====================================================
     */
    public function index()
    {
        $kasus = Kasus::query()
            ->select([
                'KasusID',
                'ClientID',
                'NamaTugas',
                'NamaFile',
            ])
            ->with([
                'client:ClientID,NamaClient',
                'kelas:id,kode_kelas,tipe_kelas,KasusID',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'KasusID' => $item->KasusID,

                    'KelasID' => $item->kelas
                        ? $item->kelas->id
                        : null,

                    'kode_kelas' => $item->kelas
                        ? $item->kelas->kode_kelas
                        : null,

                    'TipeKelas' => $item->kelas
                        ? $item->kelas->tipe_kelas
                        : null,

                    'KasusID_Kelas' => $item->kelas
                        ? $item->kelas->KasusID
                        : null,

                    'ClientID' => $item->ClientID,

                    'NamaClient' => $item->client
                        ? $item->client->NamaClient
                        : null,

                    'NamaTugas' => $item->NamaTugas,
                    'NamaFile' => $item->NamaFile,

                    'NamaKelas' => $item->kelas
                        ? $item->kelas->kode_kelas
                        : null,
                ];
            });

        return response()->json($kasus);
    }


    /**
     * =====================================================
     * STORE
     * =====================================================
     *
     * Membuat 1 Kasus baru untuk 1 record Kelas.
     *
     * Relasi:
     *
     * kelas.KasusID
     *       ↓
     * kasus.KasusID
     *
     * Tidak ada KelasID di tabel kasus.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'kode_kelas' => [
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


        /*
         * Cari record Kelas berdasarkan:
         *
         * kode_kelas + tipe_kelas
         *
         * Contoh:
         *
         * LA01 + UTS
         * LA01 + UAS
         * LB01 + UTS
         */
        $kelas = Kelas::where(
            'kode_kelas',
            $validated['kode_kelas']
        )
            ->where(
                'tipe_kelas',
                $validated['TipeKelas']
            )
            ->first();


        if (!$kelas) {
            return response()->json([
                'message' =>
                    'Kelas ' .
                    $validated['kode_kelas'] .
                    ' dengan tipe ' .
                    $validated['TipeKelas'] .
                    ' tidak ditemukan.',
            ], 404);
        }


        /*
         * Karena relasinya 1 : 1,
         * satu record kelas hanya boleh memiliki
         * satu KasusID.
         */
        if ($kelas->KasusID !== null) {
            return response()->json([
                'message' =>
                    'Kelas ' .
                    $kelas->kode_kelas .
                    ' dengan tipe ' .
                    $kelas->tipe_kelas .
                    ' sudah memiliki tugas.',
            ], 409);
        }


        $uploadedFile = $request->file('file');

        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );


        /*
         * Semua proses dibuat dalam satu transaction.
         */
        $result = DB::transaction(function () use (
            $validated,
            $uploadedFile,
            $fileContent,
            $kelas
        ) {

            /*
             * Setiap tugas memiliki DataClient sendiri.
             *
             * Walaupun NamaClient sama,
             * tetap dibuat ClientID baru.
             */
            $client = DataClient::create([
                'NamaClient' => trim(
                    $validated['NamaClient']
                ),
            ]);


            /*
             * Buat Kasus BARU.
             *
             * Tidak ada KelasID.
             *
             * KasusID otomatis dibuat oleh database.
             */
            $kasus = Kasus::create([

                'ClientID' =>
                    $client->ClientID,

                'NamaTugas' =>
                    $validated['NamaTugas'],

                'NamaFile' =>
                    $uploadedFile->getClientOriginalName(),

                'File' =>
                    $fileContent,
            ]);


            /*
             * Hubungkan Kasus dengan Kelas.
             *
             * kelas.KasusID → kasus.KasusID
             */
            $kelas->update([
                'KasusID' => $kasus->KasusID,
            ]);


            return [
                'kasus' => $kasus,
                'client' => $client,
                'kelas' => $kelas->fresh(),
            ];
        });


        return response()->json([

            'message' =>
                'Tugas berhasil dibuat.',

            'data' => [

                'KasusID' =>
                    $result['kasus']->KasusID,

                'KelasID' =>
                    $result['kelas']->id,

                'kode_kelas' =>
                    $result['kelas']->kode_kelas,

                'TipeKelas' =>
                    $result['kelas']->tipe_kelas,

                'ClientID' =>
                    $result['client']->ClientID,

                'NamaClient' =>
                    $result['client']->NamaClient,

                'NamaTugas' =>
                    $result['kasus']->NamaTugas,

                'NamaFile' =>
                    $result['kasus']->NamaFile,

                'NamaKelas' =>
                    $result['kelas']->kode_kelas,
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


        $clientID = $kasus->ClientID;


        DB::transaction(function () use (
            $kasus,
            $clientID
        ) {

            /*
             * Lepaskan KasusID dari Kelas terlebih dahulu.
             */
            Kelas::where(
                'KasusID',
                $kasus->KasusID
            )->update([
                'KasusID' => null,
            ]);


            /*
             * Hapus kasus.
             */
            $kasus->delete();


            /*
             * Karena Client dibuat khusus untuk kasus ini,
             * Client juga dihapus.
             */
            if ($clientID) {
                DataClient::where(
                    'ClientID',
                    $clientID
                )->delete();
            }
        });


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