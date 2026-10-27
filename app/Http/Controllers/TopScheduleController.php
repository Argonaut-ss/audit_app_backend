<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Services\TopScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopScheduleController extends Controller
{
    public function __construct(
        protected TopScheduleService $topScheduleService
    ) {}

    protected function resolveAuthorizedJwbKasus(Request $request, int $jwbKasusId): JwbKasus
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->first();

        if (! $jwbKasus) {
            abort(404, 'Data kasus tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return $jwbKasus;
    }

    /**
     * Mengambil data satu Top Schedule (A s/d N) beserta perhitungan AJE/RJE
     * dan rincian jurnal pembentuknya untuk modal detail (i).
     */
    public function show(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = $this->resolveAuthorizedJwbKasus($request, $jwbKasusId);
        $topLetter = $request->query('top', 'A');

        $data = $this->topScheduleService->getTopScheduleData($jwbKasus, $topLetter);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Menyimpan konfigurasi tanggal header dan susunan baris akun Top Schedule.
     */
    public function store(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = $this->resolveAuthorizedJwbKasus($request, $jwbKasusId);

        // Normalisasi format tanggal dd/mm/yyyy ke YYYY-MM-DD jika diinput oleh user
        $raw = $request->all();
        foreach (['TanggalPerBook', 'TanggalAuditBerjalan', 'TanggalAuditSebelum'] as $dateField) {
            if (! empty($raw[$dateField]) && is_string($raw[$dateField])) {
                $val = trim($raw[$dateField]);
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
                    $raw[$dateField] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                }
            }
        }
        $request->merge($raw);

        $validated = $request->validate([
            'top' => ['required', 'string', 'max:2'],
            'TanggalPerBook' => ['nullable', 'date'],
            'TanggalAuditBerjalan' => ['nullable', 'date'],
            'TanggalAuditSebelum' => ['nullable', 'date'],
            'IsFinal' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.COAID' => ['required_with:items', 'integer', 'exists:coa,COAID'],
            'items.*.SubMapping' => ['nullable', 'string', 'max:100'],
            'items.*.Urutan' => ['nullable', 'integer'],
        ]);

        $this->topScheduleService->saveTopSchedule($jwbKasus, $validated);

        $freshData = $this->topScheduleService->getTopScheduleData($jwbKasus, $validated['top']);

        return response()->json([
            'success' => true,
            'message' => 'Top Schedule berhasil disimpan.',
            'data' => $freshData,
        ]);
    }
}

