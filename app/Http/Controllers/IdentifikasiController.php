<?php
// app/Http/Controllers/Api/IdentifikasiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IdentifikasiController extends Controller
{

    public function show(int $jwbKasusId): JsonResponse
    {
        $identifikasi = Identifikasi::where('JwbKasusID', $jwbKasusId)->first();

        if (!$identifikasi) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Data identifikasi belum ada untuk kasus ini.'
            ], 200);
        }

        $identifikasi->FileAkteUrl = $identifikasi->FileAkte 
            ? Storage::url($identifikasi->FileAkte) 
            : null;
        $identifikasi->FileNPWPUrl = $identifikasi->FileNPWP 
            ? Storage::url($identifikasi->FileNPWP) 
            : null;
        $identifikasi->FileStrukturOrgUrl = $identifikasi->FileStrukturOrg 
            ? Storage::url($identifikasi->FileStrukturOrg) 
            : null;

        return response()->json([
            'success' => true,
            'data' => $identifikasi
        ]);
    }

    public function update(Request $request, int $jwbKasusId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'Tahun' => 'nullable|integer|min:1900|max:2100',
            'OpiniAudit' => 'nullable|string|max:255',
            'NoSuratPengesahan' => 'nullable|string|max:255',
            'LaporanSPT' => 'nullable|string|max:255',
            'NoSuratKeputusan' => 'nullable|string|max:255',
            'LaporanKeuangan' => 'nullable|string|max:255',
            'TipePerikatan' => 'nullable|string|max:255',
            'SumberDana' => 'nullable|string|max:255',
            'JenisPerikatan' => 'nullable|string|max:255',
            'TujuanTransaksi' => 'nullable|string|max:255',
            'StandardAkutansi' => 'nullable|string|max:255',
            'TotalAset' => 'nullable|integer|min:0',
            'NamaKAP' => 'nullable|string|max:255',
            'Pendapatan' => 'nullable|integer|min:0',
            'LabaRugi' => 'nullable|integer',
            'KontakNama' => 'nullable|string|max:255',
            'KontakJabatan' => 'nullable|string|max:255',
            'KontakNomor' => 'nullable|string|max:255',
            'KontakEmail' => 'nullable|email|max:255',
            'FileAkte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'FileNPWP' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'FileStrukturOrg' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $identifikasi = Identifikasi::firstOrNew(['JwbKasusID' => $jwbKasusId]);

        $fields = [
            'Tahun', 'OpiniAudit', 'NoSuratPengesahan', 'LaporanSPT',
            'NoSuratKeputusan', 'LaporanKeuangan', 'TipePerikatan',
            'SumberDana', 'JenisPerikatan', 'TujuanTransaksi',
            'StandardAkutansi', 'TotalAset', 'NamaKAP', 'Pendapatan',
            'LabaRugi', 'KontakNama', 'KontakJabatan', 'KontakNomor',
            'KontakEmail',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $identifikasi->$field = $request->input($field);
            }
        }

        $fileFields = ['FileAkte', 'FileNPWP', 'FileStrukturOrg'];
        foreach ($fileFields as $fileField) {
            if ($request->hasFile($fileField)) {
                // Delete old file if exists
                if ($identifikasi->$fileField) {
                    Storage::disk('public')->delete($identifikasi->$fileField);
                }
                $path = $request->file($fileField)->store(
                    "identifikasi/{$jwbKasusId}",
                    'public'
                );
                $identifikasi->$fileField = $path;
            }
        }

        $identifikasi->save();

        return response()->json([
            'success' => true,
            'message' => 'Data identifikasi berhasil disimpan.',
            'data' => $identifikasi
        ]);
    }

    public function deleteFile(int $jwbKasusId, string $field): JsonResponse
    {
        $allowedFields = ['FileAkte', 'FileNPWP', 'FileStrukturOrg'];
        
        if (!in_array($field, $allowedFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Field file tidak valid.'
            ], 400);
        }

        $identifikasi = Identifikasi::where('JwbKasusID', $jwbKasusId)->first();

        if (!$identifikasi || !$identifikasi->$field) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.'
            ], 404);
        }

        Storage::disk('public')->delete($identifikasi->$field);
        $identifikasi->$field = null;
        $identifikasi->save();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus.'
        ]);
    }
}