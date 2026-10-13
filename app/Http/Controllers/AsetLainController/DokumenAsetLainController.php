<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\DokumenAsetLain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DokumenAsetLainController extends Controller
{
    private function dokumenCheck(int $asetLainId): void
    {
        $asetLain = AsetLain::find($asetLainId);

        if (! $asetLain) {
            return;
        }

        $hasDokumen = DokumenAsetLain::where(
            'AsetLainID',
            $asetLainId
        )->exists();

        $asetLain->updateQuietly([
            'DokumenCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/aset-lain/{asetLainId}/dokumen
     *
     * List all documents belonging to an aset lain.
     */
    public function index(int $asetLainId): JsonResponse
    {
        $dokumen = DokumenAsetLain::where(
            'AsetLainID',
            $asetLainId
        )
            ->select([
                'DokumenAsetLainID',
                'AsetLainID',
                'TipeFile',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/aset-lain/{asetLainId}/dokumen
     *
     * Store a new document and its associated data.
     */
    public function store(
        Request $request,
        int $asetLainId
    ): JsonResponse {
        if (! AsetLain::find($asetLainId)) {
            return response()->json([
                'message' => 'Data aset lain tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'TipeFile' => [
                'required',
                Rule::in([
                    'Rincian',
                    'Buku Besar',
                    'Lain-lain',
                ]),
            ],
            'NamaFile' => [
                'nullable',
                'string',
                'max:255',
            ],
            'File' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:16384',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        /*
         * Lain-lain requires a custom name.
         */
        if (
            $data['TipeFile'] === 'Lain-lain'
            && empty($data['NamaFile'])
        ) {
            return response()->json([
                'message' => 'NamaFile wajib diisi untuk tipe Lain-lain.',
            ], 422);
        }

        /*
         * Default names for predefined document types.
         */
        if ($data['TipeFile'] === 'Rincian') {
            $data['NamaFile'] = 'Rincian';
        }

        if ($data['TipeFile'] === 'Buku Besar') {
            $data['NamaFile'] = 'Buku Besar';
        }

        /*
         * Store the uploaded file as MEDIUMBLOB
         * and store its detected MIME type.
         */
        $uploadedFile = $request->file('File');

        $data['File'] = $uploadedFile->get();
        $data['MimeType'] = $uploadedFile->getMimeType();
        $data['NamaFileUpload'] =
            $uploadedFile->getClientOriginalName();

        $dokumen = DokumenAsetLain::create([
            'AsetLainID' => $asetLainId,
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'] ?? null,
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($asetLainId);

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => [
                'DokumenAsetLainID' => $dokumen->DokumenAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/dokumen-aset-lain/{dokumenId}
     *
     * Get one document's associated data.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => [
                'DokumenAsetLainID' => $dokumen->DokumenAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
                'MimeType' => $dokumen->MimeType,
                'File' => $dokumen->File
                    ? base64_encode($dokumen->File)
                    : null,
                'created_at' => $dokumen->created_at,
                'updated_at' => $dokumen->updated_at,
            ],
        ]);
    }

    /**
     * PUT /api/dokumen-aset-lain/{dokumenId}
     *
     * Update a document and its associated data.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = DokumenAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'TipeFile' => [
                'required',
                Rule::in([
                    'Rincian',
                    'Buku Besar',
                    'Lain-lain',
                ]),
            ],
            'NamaFile' => [
                'nullable',
                'string',
                'max:255',
            ],
            'File' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:16384',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        /*
         * Lain-lain requires a custom name.
         */
        if (
            $data['TipeFile'] === 'Lain-lain'
            && empty($data['NamaFile'])
        ) {
            return response()->json([
                'message' => 'NamaFile wajib diisi untuk tipe Lain-lain.',
            ], 422);
        }

        /*
         * Default names for predefined document types.
         */
        if ($data['TipeFile'] === 'Rincian') {
            $data['NamaFile'] = 'Rincian';
        }

        if ($data['TipeFile'] === 'Buku Besar') {
            $data['NamaFile'] = 'Buku Besar';
        }

        /*
         * Replace the stored file only when a new file
         * is uploaded. Otherwise preserve the existing file.
         */
        if ($request->hasFile('File')) {
            $uploadedFile = $request->file('File');

            $data['File'] = $uploadedFile->get();
            $data['NamaFileUpload'] =
                $uploadedFile->getClientOriginalName();
            $data['MimeType'] = $uploadedFile->getMimeType();
        } else {
            $data['File'] = $dokumen->File;
            $data['NamaFileUpload'] = $dokumen->NamaFileUpload;
            $data['MimeType'] = $dokumen->MimeType;
        }

        $dokumen->update([
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'] ?? null,
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($dokumen->AsetLainID);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => [
                'DokumenAsetLainID' => $dokumen->DokumenAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/dokumen-aset-lain/{dokumenId}
     *
     * Delete a document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $asetLainId = $dokumen->AsetLainID;

        $dokumen->delete();

        $this->dokumenCheck($asetLainId);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}