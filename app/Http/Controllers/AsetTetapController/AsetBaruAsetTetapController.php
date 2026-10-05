<?php

namespace App\Http\Controllers\AsetTetapController;

use App\Http\Controllers\Controller;
use App\Models\AsetTetap\AsetBaruAsetTetap;
use App\Models\AsetTetap\AsetTetap;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsetBaruAsetTetapController extends Controller
{
    private const FILE_FIELDS = [
        'FotoAset' => 'TipeFileAset',
        'FotoBukti' => 'TipeFileBukti',
    ];

    private function resolveAuthorizedAsetTetap(Request $request, int $asetTetapId): AsetTetap
    {
        $asetTetap = AsetTetap::findOrFail($asetTetapId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetTetap->JwbKasusID)
            ->firstOrFail();

        return $asetTetap;
    }

    private function validationRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'NamaAset' => [$required, 'string', 'max:255'],
            'KodeAset' => [$required, 'string', 'max:255'],
            'Tanggal' => [$required, 'date'],
            'HargaPerolehan' => [$required, 'integer'],
            'FotoAset' => ['sometimes', 'nullable', 'file', 'max:16000'],
            'FotoBukti' => ['sometimes', 'nullable', 'file', 'max:16000'],
        ];
    }

    private function storeUploadedFiles(Request $request, AsetBaruAsetTetap $item, string $prefix = ''): void
    {
        foreach (self::FILE_FIELDS as $fileField => $typeField) {
            $inputName = $prefix . $fileField;
            if (! $request->hasFile($inputName)) {
                continue;
            }

            $file = $request->file($inputName);
            $item->{$fileField} = file_get_contents($file->getRealPath());
            $item->{$typeField} = $file->getMimeType() ?: 'application/octet-stream';
        }
    }

    private function serializeItem(AsetBaruAsetTetap $item): array
    {
        return [
            'AsetBaruID' => $item->AsetBaruID,
            'AsetTetapID' => $item->AsetTetapID,
            'NamaAset' => $item->NamaAset,
            'KodeAset' => $item->KodeAset,
            'Tanggal' => $item->Tanggal?->format('Y-m-d'),
            'HargaPerolehan' => $item->HargaPerolehan,
            'TipeFileAset' => $item->TipeFileAset,
            'TipeFileBukti' => $item->TipeFileBukti,
            'hasFotoAset' => $item->FotoAset !== null,
            'hasFotoBukti' => $item->FotoBukti !== null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetTetapID' => ['required', 'integer', 'exists:aset_tetap,AsetTetapID'],
        ]);
        $asetTetap = $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $validated['AsetTetapID']
        );

        return response()->json([
            'success' => true,
            'data' => $asetTetap->asetBaru()
                ->orderBy('AsetBaruID')
                ->get()
                ->map(fn (AsetBaruAsetTetap $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetTetapID' => ['required', 'integer', 'exists:aset_tetap,AsetTetapID'],
            ...$this->validationRules(),
        ]);
        $asetTetap = $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $validated['AsetTetapID']
        );

        $item = new AsetBaruAsetTetap;
        $item->AsetTetapID = $asetTetap->AsetTetapID;
        $item->fill($validated);
        $this->storeUploadedFiles($request, $item);
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Data aset baru berhasil disimpan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function show(Request $request, AsetBaruAsetTetap $aset_baru_aset_tetap): JsonResponse
    {
        $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $aset_baru_aset_tetap->AsetTetapID
        );

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($aset_baru_aset_tetap),
        ]);
    }

    public function update(Request $request, AsetBaruAsetTetap $aset_baru_aset_tetap): JsonResponse
    {
        $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $aset_baru_aset_tetap->AsetTetapID
        );
        $validated = $request->validate($this->validationRules(true));
        $aset_baru_aset_tetap->fill($validated);
        $this->storeUploadedFiles($request, $aset_baru_aset_tetap);
        $aset_baru_aset_tetap->save();

        return response()->json([
            'success' => true,
            'message' => 'Data aset baru berhasil diperbarui.',
            'data' => $this->serializeItem($aset_baru_aset_tetap->fresh()),
        ]);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetTetapID' => ['required', 'integer', 'exists:aset_tetap,AsetTetapID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.AsetBaruID' => [
                'nullable',
                'integer',
                'exists:aset_baru_aset_tetap,AsetBaruID',
            ],
            'rows.*.NamaAset' => ['required', 'string', 'max:255'],
            'rows.*.KodeAset' => ['required', 'string', 'max:255'],
            'rows.*.Tanggal' => ['required', 'date'],
            'rows.*.HargaPerolehan' => ['required', 'integer'],
            'rows.*.FotoAset' => ['sometimes', 'nullable', 'file', 'max:16000'],
            'rows.*.FotoBukti' => ['sometimes', 'nullable', 'file', 'max:16000'],
        ]);

        $asetTetap = $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $validated['AsetTetapID']
        );
        $ids = collect($validated['rows'])
            ->pluck('AsetBaruID')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $existing = AsetBaruAsetTetap::whereIn('AsetBaruID', $ids)
            ->get()
            ->keyBy('AsetBaruID');

        foreach ($validated['rows'] as $index => $row) {
            if (empty($row['AsetBaruID'])) {
                continue;
            }

            $item = $existing->get((int) $row['AsetBaruID']);
            if (! $item || (int) $item->AsetTetapID !== (int) $asetTetap->AsetTetapID) {
                throw ValidationException::withMessages([
                    "rows.$index.AsetBaruID" => 'Data aset bukan milik Aset Tetap ini.',
                ]);
            }
        }

        $saved = DB::transaction(function () use ($request, $validated, $existing, $asetTetap) {
            $result = [];

            foreach ($validated['rows'] as $index => $row) {
                $item = ! empty($row['AsetBaruID'])
                    ? $existing->get((int) $row['AsetBaruID'])
                    : new AsetBaruAsetTetap;
                $item->AsetTetapID = $asetTetap->AsetTetapID;
                $item->fill($row);
                $this->storeUploadedFiles($request, $item, "rows.$index.");
                $item->save();
                $result[] = $item->fresh();
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data aset baru berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (AsetBaruAsetTetap $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function file(Request $request, int $id, string $field)
    {
        $item = AsetBaruAsetTetap::findOrFail($id);
        $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $item->AsetTetapID
        );

        if (! array_key_exists($field, self::FILE_FIELDS)) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis file aset tidak valid.',
            ], 422);
        }

        $content = $item->{$field};
        if ($content === null) {
            return response()->json([
                'success' => false,
                'message' => 'File aset tidak ditemukan.',
            ], 404);
        }

        return response($content, 200, [
            'Content-Type' => $item->{self::FILE_FIELDS[$field]}
                ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="aset-' . $id . '-' . strtolower($field) . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function destroy(Request $request, AsetBaruAsetTetap $aset_baru_aset_tetap): JsonResponse
    {
        $this->resolveAuthorizedAsetTetap(
            $request,
            (int) $aset_baru_aset_tetap->AsetTetapID
        );
        $aset_baru_aset_tetap->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data aset baru berhasil dihapus.',
        ]);
    }
}
