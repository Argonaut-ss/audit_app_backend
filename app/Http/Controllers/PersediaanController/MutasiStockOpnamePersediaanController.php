<?php

namespace App\Http\Controllers\PersediaanController;

use App\Http\Controllers\Controller;
use App\Models\Persediaan\MutasiStockOpnamePersediaan;
use App\Models\Persediaan\Persediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MutasiStockOpnamePersediaanController extends Controller
{
    private function mutasiStockCheck(int $persediaanId): void
    {
        $persediaan = Persediaan::find($persediaanId);

        if (! $persediaan) {
            return;
        }

        $hasDokumen = MutasiStockOpnamePersediaan::where(
            'PersediaanID',
            $persediaanId
        )->exists();

        $persediaan->updateQuietly([
            'MutasiStockCheck' => $hasDokumen,
        ]);
    }

    /**
     * GET /api/persediaan/{persediaanId}/mutasi-stock-opname
     *
     * List all mutasi stock opname documents
     * belonging to a persediaan.
     */
    public function index(int $persediaanId): JsonResponse
    {
        $dokumen = MutasiStockOpnamePersediaan::where(
            'PersediaanID',
            $persediaanId
        )
            ->select([
                'MutasiStockOpnamePersediaanID',
                'PersediaanID',
                'NamaFile',
                'NamaFileUpload',
                'created_at',
                'updated_at',
            ])
            ->paginate(10);

        return response()->json([
            'message' => 'Data mutasi stock opname berhasil diambil.',
            'data' => $dokumen,
        ]);
    }

    /**
     * POST /api/persediaan/{persediaanId}/mutasi-stock-opname
     *
     * Store a new mutasi stock opname document.
     */
    public function store(
        Request $request,
        int $persediaanId
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

        $dokumen = MutasiStockOpnamePersediaan::create([
            'PersediaanID' => $persediaanId,
            'NamaFile' => $data['NamaFile'],
            'NamaFileUpload' => $data['NamaFileUpload'],
            'MimeType' => $data['MimeType'],
            'File' => $data['File'],
        ]);

        $this->mutasiStockCheck($persediaanId);

        return response()->json([
            'message' => 'Dokumen mutasi stock opname berhasil disimpan.',
            'data' => [
                'MutasiStockOpnamePersediaanID' =>
                    $dokumen->MutasiStockOpnamePersediaanID,
                'PersediaanID' => $dokumen->PersediaanID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ], 201);
    }

    /**
     * GET /api/mutasi-stock-opname-persediaan/{dokumenId}
     *
     * Get one mutasi stock opname document.
     */
    public function show(int $dokumenId): JsonResponse
    {
        $dokumen = MutasiStockOpnamePersediaan::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen mutasi stock opname tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data mutasi stock opname berhasil diambil.',
            'data' => [
                'MutasiStockOpnamePersediaanID' =>
                    $dokumen->MutasiStockOpnamePersediaanID,
                'PersediaanID' => $dokumen->PersediaanID,
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
     * PUT /api/mutasi-stock-opname-persediaan/{dokumenId}
     *
     * Update a mutasi stock opname document.
     */
    public function update(
        Request $request,
        int $dokumenId
    ): JsonResponse {
        $dokumen = MutasiStockOpnamePersediaan::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen mutasi stock opname tidak ditemukan.',
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

        $this->mutasiStockCheck($dokumen->PersediaanID);

        return response()->json([
            'message' => 'Dokumen mutasi stock opname berhasil diperbarui.',
            'data' => [
                'MutasiStockOpnamePersediaanID' =>
                    $dokumen->MutasiStockOpnamePersediaanID,
                'PersediaanID' => $dokumen->PersediaanID,
                'NamaFile' => $dokumen->NamaFile,
                'NamaFileUpload' => $dokumen->NamaFileUpload,
            ],
        ]);
    }

    /**
     * DELETE /api/mutasi-stock-opname-persediaan/{dokumenId}
     *
     * Delete a mutasi stock opname document by ID.
     */
    public function destroy(int $dokumenId): JsonResponse
    {
        $dokumen = MutasiStockOpnamePersediaan::find($dokumenId);

        if (! $dokumen) {
            return response()->json([
                'message' => 'Dokumen mutasi stock opname tidak ditemukan.',
            ], 404);
        }

        $persediaanId = $dokumen->PersediaanID;

        $dokumen->delete();

        $this->mutasiStockCheck($persediaanId);

        return response()->json([
            'message' => 'Dokumen mutasi stock opname berhasil dihapus.',
        ]);
    }
}