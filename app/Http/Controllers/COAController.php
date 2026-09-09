<?php

namespace App\Http\Controllers;

use App\Imports\COAImport;
use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class COAController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'JwbKasusID' => ['required', 'integer', 'exists:jwb_kasus,JwbKasusID'],
            'NoAkun' => ['nullable', 'integer'],
            'NamaAkun' => ['nullable', 'string', 'max:255'],
            'MappingGroup' => ['nullable', 'string', 'max:255'],
            'MapKelompok' => ['nullable', 'string', 'max:255'],
            'MappingTop' => ['nullable', 'string', 'max:255'],
            'SubMappingTop' => ['nullable', 'string', 'max:255'],
            'Saldo' => ['nullable', 'in:Debit,Kredit'],
            'PerBook' => ['nullable', 'integer'],
            'AuditSebelum' => ['nullable', 'integer'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $request->JwbKasusID)
            ->firstOrFail();

        $coa = COA::create([
            'JwbKasusID' => $jwbKasus->JwbKasusID,
            'NoAkun' => $request->NoAkun,
            'NamaAkun' => $request->NamaAkun,
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
            'NoAkun' => ['nullable', 'integer'],
            'NamaAkun' => ['nullable', 'string', 'max:255'],
            'MappingGroup' => ['nullable', 'string', 'max:255'],
            'MapKelompok' => ['nullable', 'string', 'max:255'],
            'MappingTop' => ['nullable', 'string', 'max:255'],
            'SubMappingTop' => ['nullable', 'string', 'max:255'],
            'Saldo' => ['nullable', 'in:Debit,Kredit'],
            'PerBook' => ['nullable', 'integer'],
            'AuditSebelum' => ['nullable', 'integer'],
        ]);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $coa->JwbKasusID)
            ->firstOrFail();

        $coa->update([
            'NoAkun' => $request->NoAkun,
            'NamaAkun' => $request->NamaAkun,
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
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:2048',
            ],
        ]);

        Excel::import(
            new COAImport($request->user()),
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