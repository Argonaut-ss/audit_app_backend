<?php

namespace App\Http\Controllers\PendapatanUsahaController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use App\Models\PendapatanUsaha\VouchingPU;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VouchingPUController extends Controller
{
    private const FILE_FIELDS = [
        'BuktiInternal' => 'BuktiInternalTipeFile',
        'BuktiEksternal' => 'BuktiEksternalTipeFile',
        'Bukti' => 'BuktiFileTipe',
    ];

    private function resolveAuthorizedPendapatanUsaha(
        Request $request,
        int $pendapatanUsahaId
    ): PendapatanUsaha {
        $pendapatanUsaha = PendapatanUsaha::findOrFail($pendapatanUsahaId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $pendapatanUsaha->JwbKasusID)
            ->firstOrFail();

        return $pendapatanUsaha;
    }

    private function syncVouchingCheck(PendapatanUsaha $pendapatanUsaha): void
    {
        $pendapatanUsaha->updateQuietly([
            'VouchingCheck' => $pendapatanUsaha->vouching()->exists(),
        ]);
    }

    private function validationRules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        $rules = [
            'Keterangan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'Tanggal' => [$presence, 'date'],
            'NomorBukti' => [$presence, 'string', 'max:255'],
            'NominalInternal' => [$presence, 'integer'],
            'NominalEksternal' => [$presence, 'integer'],
            'PihakRelasi' => ['sometimes', 'boolean'],
            'ApprovalPO' => ['sometimes', 'boolean'],
            'ApprovalNV' => ['sometimes', 'boolean'],
            'ApprovalDO' => ['sometimes', 'boolean'],
        ];

        foreach (self::FILE_FIELDS as $fileField => $_typeField) {
            $rules[$fileField] = ['sometimes', 'nullable', 'file', 'max:16000'];
        }

        return $rules;
    }

    private function storeUploadedFiles(Request $request, VouchingPU $item): void
    {
        foreach (self::FILE_FIELDS as $fileField => $typeField) {
            if (! $request->hasFile($fileField)) {
                continue;
            }

            $file = $request->file($fileField);
            $item->{$fileField} = file_get_contents($file->getRealPath());
            $item->{$typeField} = $file->getMimeType()
                ?: 'application/octet-stream';
        }
    }

    private function serializeItem(VouchingPU $item): array
    {
        return [
            'VouchingID' => $item->VouchingID,
            'PendapatanUsahaID' => $item->PendapatanUsahaID,
            'Keterangan' => $item->Keterangan,
            'Tanggal' => $item->Tanggal?->format('Y-m-d'),
            'NomorBukti' => $item->NomorBukti,
            'NominalInternal' => $item->NominalInternal,
            'BuktiInternalTipeFile' => $item->BuktiInternalTipeFile,
            'NominalEksternal' => $item->NominalEksternal,
            'BuktiEksternalTipeFile' => $item->BuktiEksternalTipeFile,
            'Selisih' => $item->Selisih,
            'PihakRelasi' => $item->PihakRelasi,
            'BuktiFileTipe' => $item->BuktiFileTipe,
            'ApprovalPO' => $item->ApprovalPO,
            'ApprovalNV' => $item->ApprovalNV,
            'ApprovalDO' => $item->ApprovalDO,
            'hasBuktiInternal' => $item->BuktiInternal !== null,
            'hasBuktiEksternal' => $item->BuktiEksternal !== null,
            'hasBukti' => $item->Bukti !== null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function calculateSelisih(int $nominalInternal, int $nominalEksternal): int
    {
        return $nominalInternal - $nominalEksternal;
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PendapatanUsahaID' => [
                'required',
                'integer',
                'exists:pendapatan_usaha,PendapatanUsahaID',
            ],
        ]);

        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $validated['PendapatanUsahaID']
        );

        return response()->json([
            'success' => true,
            'data' => $pendapatanUsaha->vouching()
                ->orderBy('VouchingID')
                ->get()
                ->map(fn (VouchingPU $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'PendapatanUsahaID' => [
                'required',
                'integer',
                'exists:pendapatan_usaha,PendapatanUsahaID',
            ],
            ...$this->validationRules(),
        ]);

        $pendapatanUsaha = $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $validated['PendapatanUsahaID']
        );

        $item = new VouchingPU;
        $item->PendapatanUsahaID = $pendapatanUsaha->PendapatanUsahaID;
        $item->fill($validated);
        $item->NominalEksternal = (int) ($validated['NominalEksternal'] ?? 0);
        $item->Selisih = $this->calculateSelisih(
            (int) $item->NominalInternal,
            (int) $item->NominalEksternal
        );
        $this->storeUploadedFiles($request, $item);
        $item->save();
        $this->syncVouchingCheck($pendapatanUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data vouching berhasil disimpan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = VouchingPU::findOrFail($id);
        $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $item->PendapatanUsahaID
        );

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($item),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = VouchingPU::findOrFail($id);
        $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $item->PendapatanUsahaID
        );

        $validated = $request->validate($this->validationRules(true));
        $item->fill($validated);

        if (array_key_exists('NominalInternal', $validated)
            || array_key_exists('NominalEksternal', $validated)) {
            $item->Selisih = $this->calculateSelisih(
                (int) $item->NominalInternal,
                (int) $item->NominalEksternal
            );
        }

        $this->storeUploadedFiles($request, $item);
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Data vouching berhasil diperbarui.',
            'data' => $this->serializeItem($item->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = VouchingPU::findOrFail($id);
        $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $item->PendapatanUsahaID
        );
        $pendapatanUsaha = $item->pendapatanUsaha;
        $item->delete();
        $this->syncVouchingCheck($pendapatanUsaha);

        return response()->json([
            'success' => true,
            'message' => 'Data vouching berhasil dihapus.',
        ]);
    }

    public function file(Request $request, int $id, string $field)
    {
        $item = VouchingPU::findOrFail($id);
        $this->resolveAuthorizedPendapatanUsaha(
            $request,
            (int) $item->PendapatanUsahaID
        );

        if (! array_key_exists($field, self::FILE_FIELDS)) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis file vouching tidak valid.',
            ], 422);
        }

        $content = $item->{$field};
        if ($content === null) {
            return response()->json([
                'success' => false,
                'message' => 'File vouching tidak ditemukan.',
            ], 404);
        }

        $contentType = $item->{self::FILE_FIELDS[$field]}
            ?: 'application/octet-stream';

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="vouching-' . $id . '-' . strtolower($field) . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
