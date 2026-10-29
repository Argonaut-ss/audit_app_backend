<?php

namespace App\Http\Controllers\BukuBesarController;

use App\Http\Controllers\Controller;
use App\Models\BukuBesar\BukuBesar;
use App\Models\BukuBesar\IsiBukuBesar;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Services\BukuBesarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'available_accounts' => $bukuBesarService->getAvailableAccounts($bukuBesar),
            ],
        ]);
    }

    public function update(Request $request, int $jwbKasusId, BukuBesarService $bukuBesarService): JsonResponse
    {
        $validated = $request->validate([
            'Top' => [
                'required',
                'string',
                Rule::in(array_keys(BukuBesar::TOP_MAPPINGS)),
            ],
            'TanggalAuditSblm' => ['present', 'nullable', 'date_format:Y-m-d'],
            'items' => ['sometimes', 'array'],
            'items.*.COAID' => ['sometimes', 'nullable'],
            'items.*.account' => ['sometimes', 'nullable', 'string'],
            'items.*.SubMapping' => ['sometimes', 'nullable', 'string'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();

        $bukuBesar = $this->resolveHeader($jwbKasus, $validated['Top']);
        $bukuBesar->update([
            'TanggalAuditSblm' => $validated['TanggalAuditSblm'],
        ]);

        if ($request->has('items') && is_array($request->input('items'))) {
            DB::transaction(function () use ($bukuBesar, $jwbKasusId, $request) {
                $allCoas = COA::where('JwbKasusID', $jwbKasusId)->get();
                $coaByName = $allCoas->keyBy(fn ($c) => trim(strtolower($c->NamaAkun)));
                $coaById = $allCoas->keyBy('COAID');

                IsiBukuBesar::where('BukuBesarID', $bukuBesar->BukuBesarID)->delete();

                foreach ($request->input('items') as $item) {
                    $coaId = null;
                    if (! empty($item['COAID']) && $coaById->has((int) $item['COAID'])) {
                        $coaId = (int) $item['COAID'];
                    } elseif (! empty($item['account'])) {
                        $name = trim(strtolower($item['account']));
                        if ($coaByName->has($name)) {
                            $coaId = $coaByName->get($name)->COAID;
                        }
                    }

                    if ($coaId !== null) {
                        $matchedCoa = $coaById->get($coaId);
                        $subMapping = $item['SubMapping'] ?? ($matchedCoa->SubMappingTop ?: $matchedCoa->MappingTop);

                        IsiBukuBesar::create([
                            'BukuBesarID' => $bukuBesar->BukuBesarID,
                            'COAID' => $coaId,
                            'SubMapping' => $subMapping,
                        ]);
                    }
                }
            });
        }

        $schedule = $bukuBesarService->getTopSchedule($bukuBesar);
        $periode = $jwbKasus->Periode ? \Carbon\Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        return response()->json([
            'success' => true,
            'message' => 'Data Buku Besar berhasil disimpan.',
            'data' => [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Mapping' => $bukuBesar->Mapping,
                'TanggalPerBook' => $periode,
                'TanggalAuditJln' => $periode,
                'TanggalAuditSblm' => $bukuBesar->TanggalAuditSblm?->format('Y-m-d'),
                'sub_mappings' => $schedule['sub_mappings'],
                'totals' => $schedule['totals'],
                'available_accounts' => $bukuBesarService->getAvailableAccounts($bukuBesar),
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
