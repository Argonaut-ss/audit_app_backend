<?php

namespace App\Http\Controllers;

use App\Models\DataClient;
use Illuminate\Http\Request;

class DataClientController extends Controller
{   
    public function index()
    {
        $clients = DataClient::query()
            ->select([
                'ClientID',
                'NamaClient',
                'JenisClient',
                'AlamatClient',
                'HPClient',
                'EmailClient',
                'URLClient',
                'NamaKantor',
                'AlamatKantor',
                'HPKantor',
                'EmailKantor',
                'URLKantor',
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
        ]);


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
        ]);


        $client->update($validated);


        return response()->json([
            'message' => 'Data client berhasil diperbarui.',

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
        if ($client->kasus()->exists()) {

            return response()->json([
                'message' =>
                    'Client tidak dapat dihapus karena masih digunakan oleh tugas/kasus.',
            ], 409);
        }


        $client->delete();


        return response()->json([
            'message' => 'Data client berhasil dihapus.',
        ]);
    }
}