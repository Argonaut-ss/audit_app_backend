<?php

namespace App\Http\Controllers\BukuBesarController;

use App\Http\Controllers\Controller;
use App\Models\BukuBesar\BukuBesar;
use App\Models\JwbKasus;
use App\Services\BukuBesarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuBesarController extends Controller
{
    public function show(Request $request, int $jwbKasusId, BukuBesarService $bukuBesarService): JsonResponse {
        $validated = $request->validate([
            'Top' => [
                'required',
                'string',
                Rule::in(array_keys(BukuBesar::TOP_MAPPINGS)),
            ],
            'SubMapping' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();
        $bukuBesar = $this->resolveHeader($jwbKasus, $validated['Top']);
        $schedule = $bukuBesarService->getTopSchedule(
            $bukuBesar,
            $validated['SubMapping'] ?? null
        );

        $periode = $jwbKasus->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        return response()->json([
            'success' => true,
            'data' => [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Mapping' => $bukuBesar->Mapping,
                'TanggalPerBook' => $periode,
                'TanggalAuditJln' => $periode,
                'TanggalAuditSblm' => $bukuBesar->TanggalAuditSblm?->format('Y-m-d'),
                'sub_mappings' => $schedule['sub_mappings'],
                'totals' => $schedule['totals'],
            ],
        ]);
    }

    public function update(Request $request, int $jwbKasusId): JsonResponse
    {
        $validated = $request->validate([
            'Top' => [
                'required',
                'string',
                Rule::in(array_keys(BukuBesar::TOP_MAPPINGS)),
            ],
            'TanggalAuditSblm' => ['present', 'nullable', 'date_format:Y-m-d'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();

        $bukuBesar = $this->resolveHeader($jwbKasus, $validated['Top']);
        $bukuBesar->update([
            'TanggalAuditSblm' => $validated['TanggalAuditSblm'],
        ]);

        $periode = $jwbKasus->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        return response()->json([
            'success' => true,
            'message' => 'Tanggal audit sebelumnya berhasil disimpan.',
            'data' => [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Mapping' => $bukuBesar->Mapping,
                'TanggalPerBook' => $periode,
                'TanggalAuditJln' => $periode,
                'TanggalAuditSblm' => $bukuBesar->TanggalAuditSblm?->format('Y-m-d'),
            ],
        ]);
    }

    private function resolveHeader(JwbKasus $jwbKasus, string $top): BukuBesar
    {
        return BukuBesar::firstOrCreate([
            'JwbKasusID' => $jwbKasus->JwbKasusID,
            'Mapping' => BukuBesar::TOP_MAPPINGS[$top],
        ]);
    }
}
