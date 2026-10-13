<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\BuktiPendukungAsetLain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuktiPendukungAsetLainController extends Controller
{
    private function buktiPendukungCheck(int $asetLainId): void
    {
        $asetLain = AsetLain::find($asetLainId);

        if (! $asetLain) {
            return;
        }

        $hasDokumen = BuktiPendukungAsetLain::where(
            'AsetLainID',
            $asetLainId
        )->exists();

        $asetLain->updateQuietly([
            'BuktiPendukungCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/aset-lain/{asetLainId}/bukti-pendukung
     *
     * List all supporting documents belonging to an aset lain.
     */
    public function index(int $asetLainId): JsonResponse
    {
        $dokumen = BuktiPendukungAsetLain::where(
            'AsetLainID',
            $asetLainId
        )
            ->select([
                'BuktiPendukungAsetLainID',
                'AsetLainID',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data bukti pendukung berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/aset-lain/{asetLainId}/bukti-pendukung
     *
     * Store a new supporting document.
     */
    public function store(
        Request $request,
        int $asetLainId
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
        $data['NamaFileUpload'] =
            $uploadedFile->getClientOriginalName();

        $dokumen = BuktiPendukungAsetLain::create([
            'AsetLainID' => $asetLainId,
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->buktiPendukungCheck($asetLainId);

        return response()->json([
            'message' => 'Bukti pendukung berhasil disimpan.',
            'data' => [
                'BuktiPendukungAsetLainID' =>
                    $dokumen->BuktiPendukungAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/bukti-pendukung-aset-lain/{dokumenId}
     *
     * Get one supporting document.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = BuktiPendukungAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Bukti pendukung tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data bukti pendukung berhasil diambil.',
            'data' => [
                'BuktiPendukungAsetLainID' =>
                    $dokumen->BuktiPendukungAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
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
     * PUT /api/bukti-pendukung-aset-lain/{dokumenId}
     *
     * Update a supporting document.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = BuktiPendukungAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Bukti pendukung tidak ditemukan.',
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
            $data['MimeType'] = $uploadedFile->getMimeType();
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

        $this->buktiPendukungCheck(
            (int) $dokumen->AsetLainID
        );

        return response()->json([
            'message' => 'Bukti pendukung berhasil diperbarui.',
            'data' => [
                'BuktiPendukungAsetLainID' =>
                    $dokumen->BuktiPendukungAsetLainID,
                'AsetLainID' => $dokumen->AsetLainID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/bukti-pendukung-aset-lain/{dokumenId}
     *
     * Delete a supporting document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = BuktiPendukungAsetLain::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Bukti pendukung tidak ditemukan.',
            ], 404);
        }

        $asetLainId = (int) $dokumen->AsetLainID;

        $dokumen->delete();

        $this->buktiPendukungCheck($asetLainId);

        return response()->json([
            'message' => 'Bukti pendukung berhasil dihapus.',
        ]);
    }
}