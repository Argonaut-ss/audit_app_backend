<?php

namespace App\Http\Controllers;

use App\Models\Perikatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerikatanController extends Controller
{
    /*
     * =====================================================
     * SHOW
     * =====================================================
     */

    public function show(Request $request, $id): JsonResponse
    {
        $perikatan = Perikatan::with([
            'jwbKasus',
        ])->findOrFail($id);

        return response()->json($perikatan);
    }


    /*
     * =====================================================
     * UPDATE
     * =====================================================
     */

    public function update(Request $request, $id): JsonResponse
    {
        $perikatan = Perikatan::findOrFail($id);

        $validated = $request->validate([

            'FileProposal' => [
                'nullable',
                'file',
            ],

            'FileSPK' => [
                'nullable',
                'file',
            ],

            'FileSuratTugas' => [
                'nullable',
                'file',
            ],

            'FilePenugasan' => [
                'nullable',
                'file',
            ],

            'FileIndependensi' => [
                'nullable',
                'file',
            ],

            'Pembuat' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);


        $data = [];


        if ($request->hasFile('FileProposal')) {
            $data['FileProposal'] =
                file_get_contents(
                    $request->file('FileProposal')->getRealPath()
                );
        }


        if ($request->hasFile('FileSPK')) {
            $data['FileSPK'] =
                file_get_contents(
                    $request->file('FileSPK')->getRealPath()
                );
        }


        if ($request->hasFile('FileSuratTugas')) {
            $data['FileSuratTugas'] =
                file_get_contents(
                    $request->file('FileSuratTugas')->getRealPath()
                );
        }


        if ($request->hasFile('FilePenugasan')) {
            $data['FilePenugasan'] =
                file_get_contents(
                    $request->file('FilePenugasan')->getRealPath()
                );
        }


        if ($request->hasFile('FileIndependensi')) {
            $data['FileIndependensi'] =
                file_get_contents(
                    $request->file('FileIndependensi')->getRealPath()
                );
        }


        if ($request->filled('Pembuat')) {
            $data['Pembuat'] =
                $validated['Pembuat'];
        }


        $perikatan->update($data);


        return response()->json([
            'message' =>
                'Data perikatan berhasil diperbarui.',

            'data' => [
                'PerikatanID' =>
                    $perikatan->PerikatanID,

                'JwbKasusID' =>
                    $perikatan->JwbKasusID,

                'Pembuat' =>
                    $perikatan->Pembuat,
            ],
        ]);
    }
}