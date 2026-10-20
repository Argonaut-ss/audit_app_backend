<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\AsetTetap\SetaraKas;
use App\Models\AsetTetap\RekeningKoranSetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekeningKoranSetaraKasController extends Controller
{
    private function rekeningKoranCheck(int $setaraKasId): void
    {
        $setaraKas = SetaraKas::find($setaraKasId);

        if (! $setaraKas) {
            return;
        }

        $hasDokumen = RekeningKoranSetaraKas::where(
            'SetaraKasID',
            $setaraKasId
        )->exists();

        $setaraKas->updateQuietly([
            'RekeningKoranCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/setara-kas/{setaraKasId}/rekening-koran
     *
     * List all rekening koran documents
     * belonging to a setara kas.
     */
    public function index(int $setaraKasId): JsonResponse
    {
        $dokumen = RekeningKoranSetaraKas::where(
            'SetaraKasID',
            $setaraKasId
        )
            ->select([
                'RekeningKoranSetaraKasID',
                'SetaraKasID',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data rekening koran berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/setara-kas/{setaraKasId}/rekening-koran
     *
     * Store a new rekening koran document.
     */
    public function store(
        Request $request,
        int $setaraKasId
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

        $dokumen = RekeningKoranSetaraKas::create([
            'SetaraKasID' => $setaraKasId,
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->rekeningKoranCheck($setaraKasId);

        return response()->json([
            'message' => 'Dokumen rekening koran berhasil disimpan.',
            'data' => [
                'RekeningKoranSetaraKasID' =>
                    $dokumen->RekeningKoranSetaraKasID,
                'SetaraKasID' => $dokumen->SetaraKasID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/rekening-koran-setara-kas/{dokumenId}
     *
     * Get one rekening koran document.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = RekeningKoranSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen rekening koran tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data rekening koran berhasil diambil.',
            'data' => [
                'RekeningKoranSetaraKasID' =>
                    $dokumen->RekeningKoranSetaraKasID,
                'SetaraKasID' => $dokumen->SetaraKasID,
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
     * PUT /api/rekening-koran-setara-kas/{dokumenId}
     *
     * Update a rekening koran document.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = RekeningKoranSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen rekening koran tidak ditemukan.',
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

        $this->rekeningKoranCheck($dokumen->SetaraKasID);

        return response()->json([
            'message' => 'Dokumen rekening koran berhasil diperbarui.',
            'data' => [
                'RekeningKoranSetaraKasID' =>
                    $dokumen->RekeningKoranSetaraKasID,
                'SetaraKasID' => $dokumen->SetaraKasID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/rekening-koran-setara-kas/{dokumenId}
     *
     * Delete a rekening koran document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = RekeningKoranSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen rekening koran tidak ditemukan.',
            ], 404);
        }

        $setaraKasId = $dokumen->SetaraKasID;

        $dokumen->delete();

        $this->rekeningKoranCheck($setaraKasId);

        return response()->json([
            'message' => 'Dokumen rekening koran berhasil dihapus.',
        ]);
    }
}