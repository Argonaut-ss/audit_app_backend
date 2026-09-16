<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DokumenController extends Controller
{
    /**
     * GET /api/piutang/{piutangId}/dokumen
     *
     * List all documents belonging to a piutang.
     */
    public function index(int $piutangId): JsonResponse
    {
        $dokumen = Dokumen::where('PiutangID', $piutangId)
            ->select([
                'DokumenID',
                'PiutangID',
                'TipeFile',
                'NamaFile',
                'TersediaDokumen',
                'Alasan',
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
     * POST /api/piutang/{piutangId}/dokumen
     *
     * Store a new document and its associated data.
     */
    public function store(Request $request, int $piutangId): JsonResponse
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

            'TersediaDokumen' => [
                'required',
                Rule::in([
                    'Ya',
                    'Tidak',
                ]),
            ],

            'Alasan' => [
                'nullable',
                'string',
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
         * If TipeFile is Lain-lain, NamaFile must come
         * from the user's custom text input.
         */
        if ($data['TipeFile'] === 'Lain-lain') {
            if (empty($data['NamaFile'])) {
                return response()->json([
                    'message' => 'NamaFile wajib diisi untuk tipe Lain-lain.',
                ], 422);
            }
        }

        /*
         * If TipeFile is Rincian or Buku Besar,
         * assign the default document name.
         */
        if ($data['TipeFile'] === 'Rincian') {
            $data['NamaFile'] = 'Rincian.pdf';
        }

        if ($data['TipeFile'] === 'Buku Besar') {
            $data['NamaFile'] = 'Buku Besar.pdf';
        }

        /*
         * If the document is unavailable:
         * - Alasan is required
         * - File must not be uploaded
         */
        if ($data['TersediaDokumen'] === 'Tidak') {
            if (empty($data['Alasan'])) {
                return response()->json([
                    'message' => 'Alasan wajib diisi jika dokumen tidak tersedia.',
                ], 422);
            }

            $data['File'] = null;
        }

        /*
         * If the document is available:
         * - File is required
         * - Alasan is not needed
         */
        if ($data['TersediaDokumen'] === 'Ya') {
            if (!$request->hasFile('File')) {
                return response()->json([
                    'message' => 'File wajib diupload jika dokumen tersedia.',
                ], 422);
            }

            $data['Alasan'] = null;
        }

        /*
         * Store the actual uploaded PDF as MEDIUMBLOB.
         */
        if ($request->hasFile('File')) {
            $data['File'] = $request->file('File')->get();
        }

        $dokumen = Dokumen::create([
            'PiutangID' => $piutangId,
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'TersediaDokumen' => $data['TersediaDokumen'],
            'Alasan' => $data['Alasan'] ?? null,
            'File' => $data['File'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => [
                'DokumenID' => $dokumen->DokumenID,
                'PiutangID' => $dokumen->PiutangID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'TersediaDokumen' => $dokumen->TersediaDokumen,
                'Alasan' => $dokumen->Alasan,
            ],
        ], 201);
    }

    /**
     * GET /api/dokumen/{dokumenId}
     *
     * Get one document's associated data.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = Dokumen::find($dokumenId);

        if (!$dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data dokumen berhasil diambil.',
            'data' => [
                'DokumenID' => $dokumen->DokumenID,
                'PiutangID' => $dokumen->PiutangID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'TersediaDokumen' => $dokumen->TersediaDokumen,
                'Alasan' => $dokumen->Alasan,
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
        $dokumen = Dokumen::find($dokumenId);

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

            'TersediaDokumen' => [
                'required',
                Rule::in([
                    'Ya',
                    'Tidak',
                ]),
            ],

            'Alasan' => [
                'nullable',
                'string',
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
         * If unavailable, require reason and clear file.
         */
        if ($data['TersediaDokumen'] === 'Tidak') {
            if (empty($data['Alasan'])) {
                return response()->json([
                    'message' => 'Alasan wajib diisi jika dokumen tidak tersedia.',
                ], 422);
            }

            $data['File'] = null;
        }

        /*
         * If available, require a file.
         */
        if ($data['TersediaDokumen'] === 'Ya') {
            if (!$request->hasFile('File') && !$dokumen->File) {
                return response()->json([
                    'message' => 'File wajib diupload jika dokumen tersedia.',
                ], 422);
            }

            $data['Alasan'] = null;
        }

        /*
         * Only replace the stored blob if a new file is uploaded.
         */
        if ($request->hasFile('File')) {
            $data['File'] = $request->file('File')->get();
        } elseif ($data['TersediaDokumen'] === 'Ya') {
            $data['File'] = $dokumen->File;
        }

        $dokumen->update([
            'TipeFile' => $data['TipeFile'],
            'NamaFile' => $data['NamaFile'],
            'TersediaDokumen' => $data['TersediaDokumen'],
            'Alasan' => $data['Alasan'] ?? null,
            'File' => $data['File'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => [
                'DokumenID' => $dokumen->DokumenID,
                'PiutangID' => $dokumen->PiutangID,
                'TipeFile' => $dokumen->TipeFile,
                'NamaFile' => $dokumen->NamaFile,
                'TersediaDokumen' => $dokumen->TersediaDokumen,
                'Alasan' => $dokumen->Alasan,
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
        $dokumen = Dokumen::find($dokumenId);

        if (!$dokumen) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        $dokumen->delete();

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}