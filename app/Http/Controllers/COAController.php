<?php

namespace App\Http\Controllers;

use App\Imports\COAImport;
use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class COAController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'JwbKasusID' => [
                'required',
                'integer',
                'exists:jwb_kasus,JwbKasusID',
            ],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $request->JwbKasusID)
            ->firstOrFail();

        $query = COA::where(
            'JwbKasusID',
            $jwbKasus->JwbKasusID
        );

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('NoAkun', 'like', "%{$search}%")
                    ->orWhere('NamaAkun', 'like', "%{$search}%")
                    ->orWhere('NamaLain', 'like', "%{$search}%");
            });
        }

        $coas = $query
            ->orderBy('COAID')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $coas->items(),
            'meta' => [
                'current_page' => $coas->currentPage(),
                'last_page' => $coas->lastPage(),
                'per_page' => $coas->perPage(),
                'total' => $coas->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'JwbKasusID' => [
                'required',
                'integer',
                'exists:jwb_kasus,JwbKasusID',
            ],
            'NoAkun' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('coa', 'NoAkun')
                    ->where(
                        fn ($query) => $query->where(
                            'JwbKasusID',
                            $request->JwbKasusID
                        )
                    ),
            ],
            'NamaAkun' => ['nullable', 'string', 'max:255'],
            'NamaLain' => ['nullable', 'string', 'max:255'],
            'MappingGroup' => ['nullable', 'string', 'max:255'],
            'MapKelompok' => ['nullable', 'string', 'max:255'],
            'MappingTop' => ['nullable', 'string', 'max:255'],
            'SubMappingTop' => ['nullable', 'string', 'max:255'],
            'Saldo' => ['nullable', 'in:Debit,Kredit'],
            'PerBook' => ['nullable', 'numeric'],
            'AuditSebelum' => ['nullable', 'numeric'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $request->JwbKasusID)
            ->firstOrFail();

        $coa = COA::create([
            'JwbKasusID' => $jwbKasus->JwbKasusID,
            'NoAkun' => $request->NoAkun,
            'NamaAkun' => $request->NamaAkun,
            'NamaLain' => $request->NamaLain,
            'MappingGroup' => $request->MappingGroup,
            'MapKelompok' => $request->MapKelompok,
            'MappingTop' => $request->MappingTop,
            'SubMappingTop' => $request->SubMappingTop,
            'Saldo' => $request->Saldo,
            'PerBook' => $request->PerBook,
            'AuditSebelum' => $request->AuditSebelum,
        ]);

        return response()->json([
            'message' => 'COA created successfully',
            'data' => $coa,
        ], 201);
    }

    public function update(
        Request $request,
        COA $coa
    ): JsonResponse {
        $request->validate([
            'NoAkun' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('coa', 'NoAkun')
                    ->where(
                        fn ($query) => $query->where(
                            'JwbKasusID',
                            $coa->JwbKasusID
                        )
                    )
                    ->ignore($coa->COAID, 'COAID'),
            ],
            'NamaAkun' => ['nullable', 'string', 'max:255'],
            'NamaLain' => ['nullable', 'string', 'max:255'],
            'MappingGroup' => ['nullable', 'string', 'max:255'],
            'MapKelompok' => ['nullable', 'string', 'max:255'],
            'MappingTop' => ['nullable', 'string', 'max:255'],
            'SubMappingTop' => ['nullable', 'string', 'max:255'],
            'Saldo' => ['nullable', 'in:Debit,Kredit'],
            'PerBook' => ['nullable', 'numeric'],
            'AuditSebelum' => ['nullable', 'numeric'],
        ]);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $coa->JwbKasusID)
            ->firstOrFail();

        $coa->update([
            'NoAkun' => $request->NoAkun,
            'NamaAkun' => $request->NamaAkun,
            'NamaLain' => $request->NamaLain,
            'MappingGroup' => $request->MappingGroup,
            'MapKelompok' => $request->MapKelompok,
            'MappingTop' => $request->MappingTop,
            'SubMappingTop' => $request->SubMappingTop,
            'Saldo' => $request->Saldo,
            'PerBook' => $request->PerBook,
            'AuditSebelum' => $request->AuditSebelum,
        ]);

        return response()->json([
            'message' => 'COA updated successfully',
            'data' => $coa,
        ]);
    }

    public function destroy(
        Request $request,
        COA $coa
    ): JsonResponse {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $coa->JwbKasusID)
            ->firstOrFail();

        $coa->delete();

        return response()->json([
            'message' => 'COA deleted successfully',
        ]);
    }

    public function destroyAll(
        Request $request,
        int $JwbKasusID
    ): JsonResponse {
        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $JwbKasusID)
            ->firstOrFail();

        $deleted = COA::where('JwbKasusID', $JwbKasusID)->delete();

        return response()->json([
            'message' => 'All COA entries deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'JwbKasusID' => [
                'required',
                'integer',
                'exists:jwb_kasus,JwbKasusID',
            ],
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:2048',
            ],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $request->JwbKasusID)
            ->firstOrFail();

        Excel::import(
            new COAImport(
                $request->user(),
                $jwbKasus->JwbKasusID
            ),
            $request->file('file')
        );

        $result = session()->get('coa_import_result', [
            'imported' => 0,
            'skipped' => [],
            'total' => 0,
        ]);

        return response()->json([
            'message' => 'Import completed',
            'imported' => $result['imported'],
            'skipped_count' => count($result['skipped']),
            'total_rows' => $result['total'],
            'skipped_rows' => $result['skipped'],
        ]);
    }
}