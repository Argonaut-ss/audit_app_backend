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
        $perikatan->update($data);

        return response()->json([
            'message' =>
                'Data perikatan berhasil diperbarui.',

            'data' => [
                'PerikatanID' =>
                    $perikatan->PerikatanID,

                'JwbKasusID' =>
                    $perikatan->JwbKasusID,
            ],
        ]);
    }

    /*
    * =====================================================
    * DESTROY FILE
    * =====================================================
    * Menghapus salah satu file dari data perikatan.
    * Record perikatan tidak ikut dihapus.
    */

    public function destroy(Request $request, $id, $file): JsonResponse
    {
        $perikatan = Perikatan::findOrFail($id);
        $allowedFiles = [
            'FileProposal',
            'FileSPK',
            'FileSuratTugas',
            'FilePenugasan',
            'FileIndependensi',
        ];

        if (! in_array($file, $allowedFiles, true)) {
            return response()->json([
                'message' => 'Jenis file tidak valid.',
            ], 422);
        }

        if (is_null($perikatan->{$file})) {
            return response()->json([
                'message' => 'File tersebut tidak ditemukan.',
            ], 404);
        }
        $perikatan->{$file} = null;
        $perikatan->save();

        return response()->json([
            'message' => "{$file} berhasil dihapus.",
            'data' => [
                'PerikatanID' => $perikatan->PerikatanID,
                'JwbKasusID' => $perikatan->JwbKasusID,
                $file => null,
            ],
        ]);
    }
}