<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\AsetTetap\AsetTetap;
use App\Models\AsetTetap\UjiPenyusutanAsetTetap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UjiPenyusutanAsetTetapController extends Controller
{
    private function ujiPenyusutanCheck(int $asetTetapId): void
    {
        $asetTetap = AsetTetap::find($asetTetapId);

        if (! $asetTetap) {
            return;
        }

        $hasDokumen = UjiPenyusutanAsetTetap::where(
            'AsetTetapID',
            $asetTetapId
        )->exists();

        $asetTetap->updateQuietly([
            'UjiPenyusutanCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/aset-tetap/{asetTetapId}/uji-penyusutan
     *
     * List all uji penyusutan documents
     * belonging to an aset tetap.
     */
    public function index(int $asetTetapId): JsonResponse
    {
        $dokumen = UjiPenyusutanAsetTetap::where(
            'AsetTetapID',
            $asetTetapId
        )
            ->select([
                'UjiPenyusutanAsetTetapID',
                'AsetTetapID',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data uji penyusutan berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/aset-tetap/{asetTetapId}/uji-penyusutan
     *
     * Store a new uji penyusutan document.
     */
    public function store(
        Request $request,
        int $asetTetapId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'NamaFile' => [
                'required',
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

        $uploadedFile = $request->file('File');

        $data['File'] = $uploadedFile->get();
        $data['MimeType'] = $uploadedFile->getMimeType();
        $data['NamaFileUpload'] = $uploadedFile->getClientOriginalName();

        $dokumen = UjiPenyusutanAsetTetap::create([
            'AsetTetapID' => $asetTetapId,
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->ujiPenyusutanCheck($asetTetapId);

        return response()->json([
            'message' => 'Dokumen uji penyusutan berhasil disimpan.',
            'data' => [
                'UjiPenyusutanAsetTetapID' =>
                    $dokumen->UjiPenyusutanAsetTetapID,
                'AsetTetapID' => $dokumen->AsetTetapID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/uji-penyusutan-aset-tetap/{dokumenId}
     *
     * Get one uji penyusutan document.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = UjiPenyusutanAsetTetap::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen uji penyusutan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data uji penyusutan berhasil diambil.',
            'data' => [
                'UjiPenyusutanAsetTetapID' =>
                    $dokumen->UjiPenyusutanAsetTetapID,
                'AsetTetapID' => $dokumen->AsetTetapID,
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
     * PUT /api/uji-penyusutan-aset-tetap/{dokumenId}
     *
     * Update a uji penyusutan document.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = UjiPenyusutanAsetTetap::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen uji penyusutan tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'NamaFile' => [
                'required',
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

        if ($request->hasFile('File')) {
            $uploadedFile = $request->file('File');

            $data['File'] = $uploadedFile->get();
            $data['NamaFileUpload'] =
                $uploadedFile->getClientOriginalName();
            $data['MimeType'] =
                $uploadedFile->getMimeType();
        } else {
            $data['File'] = $dokumen->File;
            $data['NamaFileUpload'] = $dokumen->NamaFileUpload;
            $data['MimeType'] = $dokumen->MimeType;
        }

        $dokumen->update([
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->ujiPenyusutanCheck($dokumen->AsetTetapID);

        return response()->json([
            'message' => 'Dokumen uji penyusutan berhasil diperbarui.',
            'data' => [
                'UjiPenyusutanAsetTetapID' =>
                    $dokumen->UjiPenyusutanAsetTetapID,
                'AsetTetapID' => $dokumen->AsetTetapID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/uji-penyusutan-aset-tetap/{dokumenId}
     *
     * Delete a uji penyusutan document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = UjiPenyusutanAsetTetap::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen uji penyusutan tidak ditemukan.',
            ], 404);
        }

        $asetTetapId = $dokumen->AsetTetapID;

        $dokumen->delete();

        $this->ujiPenyusutanCheck($asetTetapId);

        return response()->json([
            'message' => 'Dokumen uji penyusutan berhasil dihapus.',
        ]);
    }
}