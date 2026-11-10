<?php

namespace App\Http\Controllers\BukuBesarController;

use App\Http\Controllers\Controller;
use App\Models\BukuBesar\BukuBesar;
use App\Models\BukuBesar\IsiBukuBesar;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Services\AuditCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BukuBesarController extends Controller
{
    public function show(Request $request, int $jwbKasusId, AuditCalculationService $calculationService): JsonResponse
    {
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
        $schedule = $this->getTopSchedule(
            $bukuBesar,
            $calculationService,
            $validated['SubMapping'] ?? null
        );

        $periode = $jwbKasus->Periode ? Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        // Tanggal audit sebelum berlaku seragam untuk seluruh TOP pada kasus audit ini
        $tanggalAuditSblm = $bukuBesar->TanggalAuditSblm
            ?? BukuBesar::where('JwbKasusID', $jwbKasus->JwbKasusID)->whereNotNull('TanggalAuditSblm')->value('TanggalAuditSblm');
        $tanggalAuditSblmFormatted = $tanggalAuditSblm ? Carbon::parse($tanggalAuditSblm)->format('Y-m-d') : null;

        return response()->json([
            'success' => true,
            'data' => [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Mapping' => $bukuBesar->Mapping,
                'TanggalPerBook' => $periode,
                'TanggalAuditJln' => $periode,
                'TanggalAuditSblm' => $tanggalAuditSblmFormatted,
                'sub_mappings' => $schedule['sub_mappings'],
                'totals' => $schedule['totals'],
                'available_accounts' => $this->getAvailableAccounts($bukuBesar, $calculationService),
            ],
        ]);
    }

    public function update(Request $request, int $jwbKasusId, AuditCalculationService $calculationService): JsonResponse
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

        // Sinkronisasi TanggalAuditSblm ke SELURUH TOP pada kasus audit ini
        $tanggalAuditSblm = $validated['TanggalAuditSblm'];
        foreach (BukuBesar::TOP_MAPPINGS as $mappingName) {
            BukuBesar::updateOrCreate(
                [
                    'JwbKasusID' => $jwbKasus->JwbKasusID,
                    'Mapping' => $mappingName,
                ],
                [
                    'TanggalAuditSblm' => $tanggalAuditSblm,
                ]
            );
        }
        $bukuBesar->refresh();

        // NOTE: Penyimpanan baris isi_buku_besar di-comment karena daftar akun
        // sudah terhubung langsung dari master COA + Jurnal Koreksi.
        // Tombol Simpan untuk menyimpan TanggalAuditSblm.
        /*
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
        */

        $schedule = $this->getTopSchedule($bukuBesar, $calculationService);
        $periode = $jwbKasus->Periode ? Carbon::parse($jwbKasus->Periode)->format('Y-m-d') : null;

        return response()->json([
            'success' => true,
            'message' => 'Data Buku Besar berhasil disimpan.',
            'data' => [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Mapping' => $bukuBesar->Mapping,
                'TanggalPerBook' => $periode,
                'TanggalAuditJln' => $periode,
                'TanggalAuditSblm' => $tanggalAuditSblm,
                'sub_mappings' => $schedule['sub_mappings'],
                'totals' => $schedule['totals'],
                'available_accounts' => $this->getAvailableAccounts($bukuBesar, $calculationService),
            ],
        ]);
    }

    // Test Rincian Pop Up
    public function rincian(
        Request $request,
        int $jwbKasusId,
        int $coaId,
        AuditCalculationService $calculationService
    ) {
        // Ensure the audit case belongs to the authenticated user.
        $jwbKasus = JwbKasus::forUser($request->user())
            ->findOrFail($jwbKasusId);

        $result = $this->calculateRincianPopUp(
            $jwbKasus->JwbKasusID,
            $coaId,
            $calculationService
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    private function getTopSchedule(
        BukuBesar $bukuBesar,
        AuditCalculationService $calculationService,
        ?string $subMapping = null
    ): array {
        $jwbKasusId = (int) $bukuBesar->JwbKasusID;
        $mapping = $bukuBesar->Mapping;
        $isiItems = $bukuBesar->isiBukuBesar()->with('coa')->orderBy('IsiBukuBesarID')->get();
        $allCorrections = $calculationService->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        if ($isiItems->isNotEmpty()) {
            $rows = $isiItems
                ->filter(fn ($item) => $item->coa !== null)
                ->filter(function ($item) use ($subMapping) {
                    $itemSubMapping = $item->SubMapping ?: ($item->coa->SubMappingTop ?: $item->coa->MappingTop);
                    return $subMapping === null || $itemSubMapping === $subMapping;
                })
                ->map(function ($item) use ($correctionsGrouped, $calculationService) {
                    $itemSubMapping = $item->SubMapping ?: ($item->coa->SubMappingTop ?: $item->coa->MappingTop);
                    return $calculationService->calculateAccount(
                        $item->coa,
                        $calculationService->getCorrectionsForCoa($item->coa, $correctionsGrouped),
                        $itemSubMapping
                    );
                })
                ->values();
        } else {
            $coas = COA::where('JwbKasusID', $jwbKasusId)
                ->where('MappingTop', $mapping)
                ->orderBy('SubMappingTop')
                ->orderBy('COAID')
                ->get()
                ->filter(function (COA $coa) use ($subMapping) {
                    return $subMapping === null || ($coa->SubMappingTop ?: $coa->MappingTop) === $subMapping;
                });

            $rows = $coas
                ->map(fn (COA $coa) => $calculationService->calculateAccount(
                    $coa,
                    $calculationService->getCorrectionsForCoa($coa, $correctionsGrouped)
                ))
                ->values();
        }

        if ($mapping === 'Kas dan Setara Kas') {
            $bankRows = $rows->filter(fn ($row) => $row['SubMapping'] === 'Rincian Setara Kas - Bank');

            if ($bankRows->isEmpty()) {
                $bankCoas = COA::where('JwbKasusID', $jwbKasusId)
                    ->where('MappingTop', 'Kas dan Setara Kas')
                    ->where('SubMappingTop', 'Rincian Setara Kas - Bank')
                    ->get();

                $bankRows = $bankCoas->map(fn ($coa) => $calculationService->calculateAccount(
                    $coa,
                    $calculationService->getCorrectionsForCoa($coa, $correctionsGrouped)
                ));
            }

            $bankTotals = $this->calculateTotals($bankRows);
            $rows = $rows->map(function ($row) use ($bankTotals, $jwbKasusId, $calculationService) {
                if ($row['NoAkun'] === '1-0000' || strtolower(trim($row['NamaAkun'])) === 'kas di bank') {
                    $row['PerBook'] = $bankTotals['PerBook'];
                    $row['AJE_RJE'] = $bankTotals['AJE_RJE'];
                    $row['AuditJln'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditSebelum'] = $bankTotals['AuditSebelum'];
                    $row['TotalDebit'] = $bankTotals['TotalDebit'] ?? 0;
                    $row['TotalKredit'] = $bankTotals['TotalKredit'] ?? 0;
                    $row['has_corrections'] = $bankTotals['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID'], $calculationService);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        if ($mapping === 'Ekuitas') {
            $pnl = $calculationService->calculatePnlSummary($jwbKasusId);
            $labaBerjalan = $pnl['laba_setelah_pajak'];
            $rows = $rows->map(function ($row) use ($labaBerjalan, $jwbKasusId, $calculationService) {
                if ($row['NoAkun'] === '3-2200' || str_contains(strtolower($row['NamaAkun']), 'laba tahun berjalan')) {
                    $row['PerBook'] = $labaBerjalan['PerBook'];
                    $row['AJE_RJE'] = $labaBerjalan['AJE_RJE'];
                    $row['AuditJln'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditSebelum'] = $labaBerjalan['AuditSebelum'];
                    $row['has_corrections'] = $labaBerjalan['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID'], $calculationService);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        $subMappings = $rows
            ->groupBy('SubMapping')
            ->map(fn (Collection $subRows, string $name) => [
                'SubMapping' => $name,
                'rows' => $subRows->values(),
                'totals' => $this->calculateTotals($subRows),
            ])
            ->values();

        if ($mapping === 'Kas dan Setara Kas') {
            $kasSub = $subMappings->firstWhere('SubMapping', 'Kas dan Setara Kas');
            $totals = $kasSub ? $kasSub['totals'] : $this->calculateTotals($rows);
        } elseif ($mapping === 'Aset Tetap') {
            $atSub = $subMappings->firstWhere('SubMapping', 'Aset Tetap');
            $akmSub = $subMappings->firstWhere('SubMapping', 'Akumulasi Penyusutan Aset Tetap');
            $atTot = $atSub ? $atSub['totals'] : $this->emptyTotals();
            $akmTot = $akmSub ? $akmSub['totals'] : $this->emptyTotals();

            $totals = [
                'PerBook' => $atTot['PerBook'] - $akmTot['PerBook'],
                'AJE_RJE' => $atTot['AJE_RJE'] - $akmTot['AJE_RJE'],
                'AuditJln' => $atTot['AuditedBerjalan'] - $akmTot['AuditedBerjalan'],
                'AuditedBerjalan' => $atTot['AuditedBerjalan'] - $akmTot['AuditedBerjalan'],
                'AuditSebelum' => $atTot['AuditSebelum'] - $akmTot['AuditSebelum'],
            ];
        } elseif ($mapping === 'Pendapatan (Beban) Lain-lain') {
            $pendSub = $subMappings->firstWhere('SubMapping', 'Pendapatan Lain-Lain');
            $bebanSub = $subMappings->firstWhere('SubMapping', 'Beban Lain-Lain');
            $pendTot = $pendSub ? $pendSub['totals'] : $this->emptyTotals();
            $bebanTot = $bebanSub ? $bebanSub['totals'] : $this->emptyTotals();

            $totals = [
                'PerBook' => $pendTot['PerBook'] - $bebanTot['PerBook'],
                'AJE_RJE' => $pendTot['AJE_RJE'] - $bebanTot['AJE_RJE'],
                'AuditJln' => $pendTot['AuditedBerjalan'] - $bebanTot['AuditedBerjalan'],
                'AuditedBerjalan' => $pendTot['AuditedBerjalan'] - $bebanTot['AuditedBerjalan'],
                'AuditSebelum' => $pendTot['AuditSebelum'] - $bebanTot['AuditSebelum'],
            ];
        } elseif ($mapping === 'Ekuitas') {
            $perBook = 0;
            $ajeRje = 0;
            $auditedBerjalan = 0;
            $auditSebelum = 0;

            foreach ($rows as $row) {
                $isDebit = strtolower($row['SaldoNormal'] ?? 'kredit') === 'debit';
                $sign = $isDebit ? -1 : 1;
                $perBook += $sign * $row['PerBook'];
                $ajeRje += $sign * $row['AJE_RJE'];
                $auditedBerjalan += $sign * $row['AuditedBerjalan'];
                $auditSebelum += $sign * $row['AuditSebelum'];
            }

            $totals = [
                'PerBook' => $perBook,
                'AJE_RJE' => $ajeRje,
                'AuditJln' => $auditedBerjalan,
                'AuditedBerjalan' => $auditedBerjalan,
                'AuditSebelum' => $auditSebelum,
            ];
        } else {
            $totals = $this->calculateTotals($rows);
        }

        return [
            'sub_mappings' => $subMappings,
            'totals' => $totals,
        ];
    }

    private function getAvailableAccounts(BukuBesar $bukuBesar, AuditCalculationService $calculationService): Collection
    {
        $jwbKasusId = (int) $bukuBesar->JwbKasusID;
        $mapping = $bukuBesar->Mapping;
        $coas = COA::where('JwbKasusID', $jwbKasusId)
            ->where('MappingTop', $mapping)
            ->orderBy('NoAkun')
            ->get();

        $allCorrections = $calculationService->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');
        $rows = $coas->map(fn (COA $coa) => $calculationService->calculateAccount(
            $coa,
            $calculationService->getCorrectionsForCoa($coa, $correctionsGrouped)
        ));

        if ($mapping === 'Kas dan Setara Kas') {
            $bankRows = $rows->filter(fn ($row) => $row['SubMapping'] === 'Rincian Setara Kas - Bank');
            $bankTotals = $this->calculateTotals($bankRows);
            $rows = $rows->map(function ($row) use ($bankTotals, $jwbKasusId, $calculationService) {
                if ($row['NoAkun'] === '1-0000' || strtolower(trim($row['NamaAkun'])) === 'kas di bank') {
                    $row['PerBook'] = $bankTotals['PerBook'];
                    $row['AJE_RJE'] = $bankTotals['AJE_RJE'];
                    $row['AuditJln'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditSebelum'] = $bankTotals['AuditSebelum'];
                    $row['TotalDebit'] = $bankTotals['TotalDebit'] ?? 0;
                    $row['TotalKredit'] = $bankTotals['TotalKredit'] ?? 0;
                    $row['has_corrections'] = $bankTotals['AJE_RJE'] !== 0;
                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID'], $calculationService);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        if ($mapping === 'Ekuitas') {
            $pnl = $calculationService->calculatePnlSummary($jwbKasusId);
            $labaBerjalan = $pnl['laba_setelah_pajak'];
            $rows = $rows->map(function ($row) use ($labaBerjalan, $jwbKasusId, $calculationService) {
                if ($row['NoAkun'] === '3-2200' || str_contains(strtolower($row['NamaAkun']), 'laba tahun berjalan')) {
                    $row['PerBook'] = $labaBerjalan['PerBook'];
                    $row['AJE_RJE'] = $labaBerjalan['AJE_RJE'];
                    $row['AuditJln'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditSebelum'] = $labaBerjalan['AuditSebelum'];
                    $row['has_corrections'] = $labaBerjalan['AJE_RJE'] !== 0;
                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID'], $calculationService);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        return $rows->values();
    }

    private function calculateRincianPopUp(
        int $jwbKasusId,
        int $coaId,
        AuditCalculationService $calculationService
    ): ?array {
        $coa = COA::where('JwbKasusID', $jwbKasusId)
            ->where('COAID', $coaId)
            ->first();

        if (! $coa) {
            return null;
        }

        $allCorrections = $calculationService->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        if ($coa->NoAkun === '1-0000' || strtolower(trim($coa->NamaAkun)) === 'kas di bank') {
            $bankCoaIds = COA::where('JwbKasusID', $jwbKasusId)
                ->where('MappingTop', 'Kas dan Setara Kas')
                ->where('SubMappingTop', 'Rincian Setara Kas - Bank')
                ->pluck('COAID');
            $corrections = $allCorrections->whereIn('COAID', $bankCoaIds);
        } elseif ($coa->NoAkun === '3-2200' || str_contains(strtolower($coa->NamaAkun), 'laba tahun berjalan')) {
            $pnlMappings = [
                'Penjualan',
                'Beban Pokok Penjualan',
                'Beban Usaha',
                'Pendapatan (Beban) Lain-lain',
                'Beban Pajak Penghasilan',
            ];
            $pnlCoaIds = COA::where('JwbKasusID', $jwbKasusId)
                ->whereIn('MappingTop', $pnlMappings)
                ->pluck('COAID');
            $corrections = $allCorrections->whereIn('COAID', $pnlCoaIds);
        } else {
            $corrections = $calculationService->getCorrectionsForCoa($coa, $correctionsGrouped);
        }

        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;
        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');
        $ajeRje = ($perBook < 0 || $auditSebelum < 0)
            ? $totalDebit - $totalKredit
            : ($isDebit ? $totalDebit - $totalKredit : $totalKredit - $totalDebit);

        $rincian = [];
        $no = 1;
        foreach ($corrections as $correction) {
            $debitVal = (int) $correction->Debit;
            $kreditVal = (int) $correction->Kredit;

            if ($debitVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Debit',
                    'sumber' => $correction->sumber,
                    'keterangan' => $correction->keterangan ?? '',
                    'nilai' => $debitVal,
                ];
            }

            if ($kreditVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Kredit',
                    'sumber' => $correction->sumber,
                    'keterangan' => $correction->keterangan ?? '',
                    'nilai' => $kreditVal,
                ];
            }
        }

        return [
            'COAID' => $coa->COAID,
            'NoAkun' => $coa->NoAkun,
            'NamaAkun' => $coa->NamaAkun,
            'SaldoNormal' => $isDebit ? 'debit' : 'kredit',
            'PerBook' => $perBook,
            'AuditSebelum' => $auditSebelum,
            'TotalDebit' => $totalDebit,
            'TotalKredit' => $totalKredit,
            'AJE_RJE' => $ajeRje,
            'rincian_jurnal' => $rincian,
        ];
    }

    private function calculateTotals(iterable $rows): array
    {
        $collection = collect($rows);

        return [
            'PerBook' => (int) $collection->sum('PerBook'),
            'AJE_RJE' => (int) $collection->sum('AJE_RJE'),
            'AuditJln' => (int) $collection->sum('AuditJln'),
            'AuditedBerjalan' => (int) $collection->sum('AuditedBerjalan'),
            'AuditSebelum' => (int) $collection->sum('AuditSebelum'),
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'PerBook' => 0,
            'AJE_RJE' => 0,
            'AuditJln' => 0,
            'AuditedBerjalan' => 0,
            'AuditSebelum' => 0,
        ];
    }

    private function resolveHeader(JwbKasus $jwbKasus, string $top): BukuBesar
    {
        return BukuBesar::firstOrCreate([
            'JwbKasusID' => $jwbKasus->JwbKasusID,
            'Mapping' => BukuBesar::TOP_MAPPINGS[$top],
        ]);
    }
}
