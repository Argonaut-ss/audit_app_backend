<?php

namespace App\Http\Controllers\UtangUsahaController;

use App\Http\Controllers\Controller;
use App\Models\UtangUsaha\DokumenUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DokumenUtangUsahaController extends Controller
{
    private function dokumenCheck(int $utangUsahaId): void
    {
        $utangUsaha = UtangUsaha::find($utangUsahaId);

        if (! $utangUsaha) {
            return;
        }

        $hasDokumen = DokumenUtangUsaha::where('UtangUsahaID', $utangUsahaId)->exists();

        $utangUsaha->updateQuietly([
            'DokumenCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/utang-usaha/{utangUsahaId}/dokumen
     *
     * List all documents belonging to a utang usaha.
     */
    public function index(int $utangUsahaId): JsonResponse
    {
        $dokumen = DokumenUtangUsaha::where('UtangUsahaID', $utangUsahaId)
            ->select([
                'DokumenUtangUsahaID',
                'UtangUsahaID',
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
     * POST /api/utang-usaha/{utangUsahaId}/dokumen
     *
     * Store a new document and its associated data.
     */
    public function store(Request $request, int $utangUsahaId): JsonResponse
    {
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
                'mimes:pdf',
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
            $data['NamaFile'] = 'Rincian.pdf';
        }

        if ($data['TipeFile'] === 'Buku Besar') {
            $data['NamaFile'] = 'Buku Besar.pdf';
        }

        /*
         * Store the actual uploaded PDF as MEDIUMBLOB.
         */
        $uploadedFile = $request->file('File');

        $data['File'] = $uploadedFile->get();

        /*
         * Store the original uploaded filename separately.
         */
        $data['NamaFileUpload'] = $uploadedFile->getClientOriginalName();

        $dokumen = DokumenUtangUsaha::create([
            'UtangUsahaID' => $utangUsahaId,
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($utangUsahaId);

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => [
                'DokumenUtangUsahaID' => $dokumen->DokumenUtangUsahaID,
                'UtangUsahaID' => $dokumen->UtangUsahaID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/dokumen/{dokumenUtangUsahaId}
     *
     * Get one document's associated data.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenUtangUsaha::find($dokumenId);

        if (!$dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => [
                'DokumenUtangUsahaID' => $dokumen->DokumenUtangUsahaID,
                'UtangUsahaID' => $dokumen->UtangUsahaID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
                'File' => $dokumen->File
                    ? base64_encode($dokumen->File)
                    : null,
                'created_at' => $dokumen->created_at,
                'updated_at' => $dokumen->updated_at,
            ],
        ]);
    }

    /**
     * PUT /api/dokumen/{dokumenId}
     *
     * Update a document and its associated data.
     */
    public function update(Request $request, int $dokumenId): JsonResponse
    {
        $dokumen = DokumenUtangUsaha::find($dokumenId);

        if (!$dokumen) {
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
                'mimes:pdf',
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
            $data['NamaFile'] = 'Rincian.pdf';
        }

        if ($data['TipeFile'] === 'Buku Besar') {
            $data['NamaFile'] = 'Buku Besar.pdf';
        }

        /*
         * Only replace the stored blob and filename if a new file is uploaded.
         */
        if ($request->hasFile('File')) {
            $uploadedFile = $request->file('File');

            $data['File'] = $uploadedFile->get();
            $data['NamaFileUpload'] = $uploadedFile->getClientOriginalName();
        } else {
            /*
             * Preserve the existing uploaded file and original filename.
             */
            $data['File'] = $dokumen->File;
            $data['NamaFileUpload'] = $dokumen->NamaFileUpload;
        }

        $dokumen->update([
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'File' => $data['File'],
        ]);

        $this->dokumenCheck($dokumen->UtangUsahaID);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => [
                'DokumenUtangUsahaID' => $dokumen->DokumenUtangUsahaID,
                'UtangUsahaID' => $dokumen->UtangUsahaID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/dokumen/{dokumenId}
     *
     * Delete a document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = DokumenUtangUsaha::find($dokumenId);

        if (!$dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $utangUsahaId = $dokumen->UtangUsahaID;
        $dokumen->delete();

        $this->dokumenCheck($utangUsahaId);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}