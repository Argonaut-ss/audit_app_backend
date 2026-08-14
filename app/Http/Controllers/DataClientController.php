<?php

namespace App\Http\Controllers;

use App\Models\DataClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DataClientController extends Controller
{
    public function index()
    {
        $clients = DataClient::query()
            ->select([
                'ClientID',
                'NamaClient',
                'JenisClient',
                'NPWP',
                'AlamatClient',
                'HPClient',
                'EmailClient',
                'URLClient',
                'NamaKantor',
                'AlamatKantor',
                'HPKantor',
                'EmailKantor',
                'URLKantor',
                'LogoKantor',
                'LogoPerusahaan',
            ])
            ->orderBy('NamaClient')
            ->get();

        return response()->json($clients);
    }


    /*
     * =====================================================
     * STORE
     * =====================================================
     */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'NamaClient' => [
                'required',
                'string',
                'max:255',
            ],

            'JenisClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NPWP' => [
                'nullable',
                'string',
                'max:255',
            ],

            'AlamatClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPClient' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailClient' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NamaKantor' => [
                'required',
                'string',
                'max:255',
            ],

            'AlamatKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPKantor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailKantor' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'LogoKantor' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'LogoPerusahaan' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);


        /*
         * =================================================
         * FOLDER LOGO
         * =================================================
         */

        $logoPath = public_path('DataClient/Logo');

        if (!File::exists($logoPath)) {
            File::makeDirectory(
                $logoPath,
                0755,
                true
            );
        }

        if ($request->hasFile('LogoKantor')) {
            $file = $request->file('LogoKantor');
            $fileName =
                'kantor_' .
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();

            $file->move(
                $logoPath,
                $fileName
            );

            $validated['LogoKantor'] =
                'DataClient/Logo/' . $fileName;
        }

        if ($request->hasFile('LogoPerusahaan')) {
            $file = $request->file('LogoPerusahaan');
            $fileName =
                'perusahaan_' .
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();

            $file->move(
                $logoPath,
                $fileName
            );

            $validated['LogoPerusahaan'] = 'DataClient/Logo/' . $fileName;
        }

        /*
         * =================================================
         * CREATE
         * =================================================
         */

        $client = DataClient::create($validated);

        return response()->json([
            'message' => 'Data client berhasil dibuat.',
            'data' => $client,
        ], 201);
    }

    /*
     * =====================================================
     * SHOW
     * =====================================================
     */

    public function show($id)
    {
        $client = DataClient::findOrFail($id);
        return response()->json($client);
    }

    /*
     * =====================================================
     * UPDATE
     * =====================================================
     */

    public function update(Request $request, $id)
    {
        $client = DataClient::findOrFail($id);
        $validated = $request->validate([

            'NamaClient' => [
                'required',
                'string',
                'max:255',
            ],

            'JenisClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NPWP' => [
                'nullable',
                'string',
                'max:255',
            ],

            'AlamatClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPClient' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailClient' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NamaKantor' => [
                'required',
                'string',
                'max:255',
            ],

            'AlamatKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPKantor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailKantor' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'LogoKantor' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'LogoPerusahaan' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $logoPath = public_path('DataClient/Logo');
        if (!File::exists($logoPath)) {
            File::makeDirectory(
                $logoPath,
                0755,
                true
            );
        }

        if ($request->hasFile('LogoKantor')) {
            if ($client->LogoKantor) {
                $oldLogo =
                    public_path($client->LogoKantor);
                if (File::exists($oldLogo)) {
                    File::delete($oldLogo);
                }
            }
            $file = $request->file('LogoKantor');
            $fileName =
                'kantor_' .
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();
            $file->move(
                $logoPath,
                $fileName
            );
            $validated['LogoKantor'] =
                'DataClient/Logo/' . $fileName;
        }

        if ($request->hasFile('LogoPerusahaan')) {
            if ($client->LogoPerusahaan) {
                $oldLogo =
                    public_path($client->LogoPerusahaan);
                if (File::exists($oldLogo)) {
                    File::delete($oldLogo);
                }
            }
            $file =
                $request->file('LogoPerusahaan');
            $fileName =
                'perusahaan_' .
                time() .
                '_' .
                uniqid() .
                '.' .
                $file->getClientOriginalExtension();
            $file->move(
                $logoPath,
                $fileName
            );
            $validated['LogoPerusahaan'] = 'DataClient/Logo/' . $fileName;
        }

        /*
         * =================================================
         * UPDATE DATABASE
         * =================================================
         */

        $client->update($validated);
        return response()->json([
            'message' =>
                'Data client berhasil diperbarui.',
            'data' => $client,
        ]);
    }

    /*
     * =====================================================
     * DESTROY
     * =====================================================
     */

    public function destroy($id)
    {
        $client = DataClient::findOrFail($id);

        /*
         * Jangan hapus client jika masih
         * digunakan oleh kasus/tugas.
         */

        if ($client->kasus()->exists()) {
            return response()->json([
                'message' =>
                    'Client tidak dapat dihapus karena masih digunakan oleh tugas/kasus.',
            ], 409);
        }
        if ($client->LogoKantor) {

            $logo =
                public_path($client->LogoKantor);
            if (File::exists($logo)) {
                File::delete($logo);
            }
        }
        if ($client->LogoPerusahaan) {

            $logo =
                public_path($client->LogoPerusahaan);
            if (File::exists($logo)) {
                File::delete($logo);
            }
        }
        $client->delete();
        return response()->json([
            'message' =>
                'Data client berhasil dihapus.',
        ]);
    }
}