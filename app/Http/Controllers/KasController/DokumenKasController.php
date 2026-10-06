<?php

namespace App\Http\Controllers\KasController;

use App\Http\Controllers\Controller;
use App\Models\Kas\DokumenKas;
use App\Models\Kas\Kas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DokumenKasController extends Controller
{
    private function dokumenCheck(int $kasId): void
    {
        $kas = Kas::find($kasId);

        if (! $kas) {
            return;
        }

        $hasDokumen = DokumenKas::where(
            'KasID',
            $kasId
        )->exists();

        $kas->updateQuietly([
            'DokumenCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/kas/{kasId}/dokumen
     *
     * List all documents belonging to a kas.
     */
    public function index(int $kasId): JsonResponse
    {
        $dokumen = DokumenKas::where(
            'KasID',
            $kasId
        )
            ->select([
                'DokumenKasID',
                'KasID',
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
     * POST /api/kas/{kasId}/dokumen
     *
     * Store a new document and its associated data.
     */
    public function store(
        Request $request,
        int $kasId
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

        $dokumen = DokumenKas::create([
            'KasID' => $kasId,
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($kasId);

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => [
                'DokumenKasID' => $dokumen->DokumenKasID,
                'KasID' => $dokumen->KasID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/dokumen-kas/{dokumenId}
     *
     * Get one document's associated data.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => [
                'DokumenKasID' => $dokumen->DokumenKasID,
                'KasID' => $dokumen->KasID,
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
     * PUT /api/dokumen-kas/{dokumenId}
     *
     * Update a document and its associated data.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = DokumenKas::find($dokumenId);

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

        $this->dokumenCheck($dokumen->KasID);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => [
                'DokumenKasID' => $dokumen->DokumenKasID,
                'KasID' => $dokumen->KasID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/dokumen-kas/{dokumenId}
     *
     * Delete a document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $kasId = $dokumen->KasID;

        $dokumen->delete();

        $this->dokumenCheck($kasId);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}