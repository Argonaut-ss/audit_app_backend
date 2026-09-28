<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\PendapatanUsaha\DokumenPendapatanUsaha;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DokumenPendapatanUsahaController extends Controller
{
    private function dokumenCheck(int $pendapatanUsahaId): void
    {
        $pendapatanUsaha = PendapatanUsaha::find($pendapatanUsahaId);

        if (! $pendapatanUsaha) {
            return;
        }

        $hasDokumen = DokumenPendapatanUsaha::where(
            'PendapatanUsahaID',
            $pendapatanUsahaId
        )->exists();

        $pendapatanUsaha->updateQuietly([
            'DokumenCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/pendapatan-usaha/{pendapatanUsahaId}/dokumen
     *
     * List all documents belonging to a pendapatan usaha.
     */
    public function index(int $pendapatanUsahaId): JsonResponse
    {
        $dokumen = DokumenPendapatanUsaha::where(
            'PendapatanUsahaID',
            $pendapatanUsahaId
        )
            ->select([
                'DokumenPendapatanUsahaID',
                'PendapatanUsahaID',
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
     * POST /api/pendapatan-usaha/{pendapatanUsahaId}/dokumen
     *
     * Store a new document and its associated data.
     */
    public function store(
        Request $request,
        int $pendapatanUsahaId
    ): JsonResponse {
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
        if ($data['TipeFile'] === 'Lain-lain') {
            if (empty($data['NamaFile'])) {
                return response()->json([
                    'message' => 'NamaFile wajib diisi untuk tipe Lain-lain.',
                ], 422);
            }
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
         * Store the uploaded file as MEDIUMBLOB and
         * store its detected MIME type.
         */
        $uploadedFile = $request->file('File');

        $data['File'] = $uploadedFile->get();
        $data['MimeType'] = $uploadedFile->getMimeType();

        /*
         * Store the original uploaded filename separately.
         */
        $data['NamaFileUpload'] = $uploadedFile->getClientOriginalName();

        $dokumen = DokumenPendapatanUsaha::create([
            'PendapatanUsahaID' => $pendapatanUsahaId,
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($pendapatanUsahaId);

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => [
                'DokumenPendapatanUsahaID'
                    => $dokumen->DokumenPendapatanUsahaID,
                'PendapatanUsahaID' => $dokumen->PendapatanUsahaID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/dokumen-pendapatan-usaha/{dokumenId}
     *
     * Get one document's associated data.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenPendapatanUsaha::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => [
                'DokumenPendapatanUsahaID'
                    => $dokumen->DokumenPendapatanUsahaID,
                'PendapatanUsahaID' => $dokumen->PendapatanUsahaID,
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
     * PUT /api/dokumen-pendapatan-usaha/{dokumenId}
     *
     * Update a document and its associated data.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = DokumenPendapatanUsaha::find($dokumenId);

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
        if ($data['TipeFile'] === 'Lain-lain') {
            if (empty($data['NamaFile'])) {
                return response()->json([
                    'message' => 'NamaFile wajib diisi untuk tipe Lain-lain.',
                ], 422);
            }
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
         * Only replace the stored file, filename, and MIME type
         * if a new file is uploaded.
         */
        if ($request->hasFile('File')) {
            $uploadedFile = $request->file('File');

            $data['File'] = $uploadedFile->get();
            $data['NamaFileUpload']
                = $uploadedFile->getClientOriginalName();
            $data['MimeType'] = $uploadedFile->getMimeType();
        } else {
            /*
             * Preserve the existing uploaded file,
             * original filename, and MIME type.
             */
            $data['File'] = $dokumen->File;
            $data['NamaFileUpload'] = $dokumen->NamaFileUpload;
            $data['MimeType'] = $dokumen->MimeType;
        }

        $dokumen->update([
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($dokumen->PendapatanUsahaID);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => [
                'DokumenPendapatanUsahaID'
                    => $dokumen->DokumenPendapatanUsahaID,
                'PendapatanUsahaID' => $dokumen->PendapatanUsahaID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/dokumen-pendapatan-usaha/{dokumenId}
     *
     * Delete a document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenPendapatanUsaha::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $pendapatanUsahaId = $dokumen->PendapatanUsahaID;

        $dokumen->delete();

        $this->dokumenCheck($pendapatanUsahaId);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}