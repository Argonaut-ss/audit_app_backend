<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\SetaraKas\SetaraKas;
use App\Models\SetaraKas\KonfirmasiBankSetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KonfirmasiBankSetaraKasController extends Controller
{
    private function konfirmasiBankCheck(int $setaraKasId): void
    {
        $setaraKas = SetaraKas::find($setaraKasId);

        if (! $setaraKas) {
            return;
        }

        $hasDokumen = KonfirmasiBankSetaraKas::where(
            'SetaraKasID',
            $setaraKasId
        )->exists();

        $setaraKas->updateQuietly([
            'KonfirmasiCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/setara-kas/{setaraKasId}/konfirmasi-bank
     *
     * List all konfirmasi bank documents
     * belonging to a setara kas.
     */
    public function index(int $setaraKasId): JsonResponse
    {
        $dokumen = KonfirmasiBankSetaraKas::where(
            'SetaraKasID',
            $setaraKasId
        )
            ->select([
                'KonfirmasiBankSetaraKasID',
                'SetaraKasID',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data konfirmasi bank berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/setara-kas/{setaraKasId}/konfirmasi-bank
     *
     * Store a new konfirmasi bank document.
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

        $dokumen = KonfirmasiBankSetaraKas::create([
            'SetaraKasID' => $setaraKasId,
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->konfirmasiBankCheck($setaraKasId);

        return response()->json([
            'message' => 'Dokumen konfirmasi bank berhasil disimpan.',
            'data' => [
                'KonfirmasiBankSetaraKasID' =>
                    $dokumen->KonfirmasiBankSetaraKasID,
                'SetaraKasID' => $dokumen->SetaraKasID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/konfirmasi-bank-setara-kas/{dokumenId}
     *
     * Get one konfirmasi bank document.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = KonfirmasiBankSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen konfirmasi bank tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data konfirmasi bank berhasil diambil.',
            'data' => [
                'KonfirmasiBankSetaraKasID' =>
                    $dokumen->KonfirmasiBankSetaraKasID,
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
     * PUT /api/konfirmasi-bank-setara-kas/{dokumenId}
     *
     * Update a konfirmasi bank document.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = KonfirmasiBankSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen konfirmasi bank tidak ditemukan.',
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

        $this->konfirmasiBankCheck($dokumen->SetaraKasID);

        return response()->json([
            'message' => 'Dokumen konfirmasi bank berhasil diperbarui.',
            'data' => [
                'KonfirmasiBankSetaraKasID' =>
                    $dokumen->KonfirmasiBankSetaraKasID,
                'SetaraKasID' => $dokumen->SetaraKasID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/konfirmasi-bank-setara-kas/{dokumenId}
     *
     * Delete a konfirmasi bank document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = KonfirmasiBankSetaraKas::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen konfirmasi bank tidak ditemukan.',
            ], 404);
        }

        $setaraKasId = $dokumen->SetaraKasID;

        $dokumen->delete();

        $this->konfirmasiBankCheck($setaraKasId);

        return response()->json([
            'message' => 'Dokumen konfirmasi bank berhasil dihapus.',
        ]);
    }
}