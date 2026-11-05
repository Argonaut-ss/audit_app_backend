<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\LaporanHasilAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanHasilAuditController extends Controller
{
    public function index()
    {
        return LaporanHasilAudit::with('files')
            ->latest()
            ->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'JwbKasusID' => [
                'required',
                'integer',
                'exists:jwb_kasus,JwbKasusID',
                'unique:laporan_hasil_audits,JwbKasusID',
            ],
            'opini_audit' => [
                'required',
                'string',
                Rule::in(LaporanHasilAudit::OPINI_AUDIT),
            ],
            'ketersediaan_atlas' => [
                'required',
                'string',
                Rule::in(LaporanHasilAudit::KETERSEDIAAN_ATLAS),
            ],
        ]);

        $laporanHasilAudit = LaporanHasilAudit::create($validated);

        return response()->json([
            'message' => 'Laporan Hasil Audit berhasil dibuat.',
            'data' => $laporanHasilAudit,
        ], 201);
    }

    public function show(LaporanHasilAudit $laporanHasilAudit)
    {
        return $laporanHasilAudit->load('jwbKasus', 'files');
    }

    public function update(Request $request, LaporanHasilAudit $laporanHasilAudit)
    {
        $validated = $request->validate([
            'opini_audit' => [
                'sometimes',
                'required',
                'string',
                Rule::in(LaporanHasilAudit::OPINI_AUDIT),
            ],
            'ketersediaan_atlas' => [
                'sometimes',
                'required',
                'string',
                Rule::in(LaporanHasilAudit::KETERSEDIAAN_ATLAS),
            ],
        ]);

        $laporanHasilAudit->update($validated);

        return response()->json([
            'message' => 'Laporan Hasil Audit berhasil diperbarui.',
            'data' => $laporanHasilAudit->fresh(),
        ]);
    }

    public function destroy(LaporanHasilAudit $laporanHasilAudit)
    {
        $laporanHasilAudit->delete();

        return response()->json([
            'message' => 'Laporan Hasil Audit berhasil dihapus.',
        ]);
    }
}