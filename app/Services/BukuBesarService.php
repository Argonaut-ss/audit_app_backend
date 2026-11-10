<?php

namespace App\Services;

use App\Models\AsetLain\PembayaranJurnalKoreksiAsetLain;
use App\Models\AsetTetap\PembayaranJurnalKoreksiAsetTetap;
use App\Models\BebanUsaha\PembayaranJurnalKoreksiBebanUsaha;
use App\Models\BukuBesar\BukuBesar;
use App\Models\COA;
use App\Models\Kas\PembayaranJurnalKoreksiKas;
use App\Models\PendapatanUsaha\PembayaranJurnalKoreksiPendapatanUsaha;
use App\Models\Persediaan\PembayaranJurnalKoreksiPersediaan;
use App\Models\Piutang\PembayaranJurnalKoreksi;
use App\Models\SetaraKas\PembayaranJurnalKoreksiSetaraKas;
use App\Models\UtangUsaha\PembayaranJurnalKoreksiUtangUsaha;
use Illuminate\Support\Collection;

class BukuBesarService
{
    /**
     * 1. AGREGASI SEMUA JURNAL KOREKSI DARI SELURUH SUBSTANTIF
     */
    public function getAggregatedCorrections(int $jwbKasusId): Collection
    {
        $sources = [
            [PembayaranJurnalKoreksiSetaraKas::class, 'setaraKas', 'Kas dan Setara Kas'],
            [PembayaranJurnalKoreksiKas::class, 'kas', 'Kas'],
            [PembayaranJurnalKoreksi::class, 'piutang', 'Piutang'],
            [PembayaranJurnalKoreksiUtangUsaha::class, 'utangUsaha', 'Utang Usaha'],
            [PembayaranJurnalKoreksiPersediaan::class, 'persediaan', 'Persediaan'],
            [PembayaranJurnalKoreksiAsetTetap::class, 'asetTetap', 'Aset Tetap'],
            [PembayaranJurnalKoreksiAsetLain::class, 'asetLain', 'Aset Lain-Lain'],
            [PembayaranJurnalKoreksiPendapatanUsaha::class, 'pendapatanUsaha', 'Pendapatan Usaha'],
            [PembayaranJurnalKoreksiBebanUsaha::class, 'bebanUsaha', 'Beban Usaha'],
        ];

        return collect($sources)->flatMap(
            function (array $sourceConfig) use ($jwbKasusId) {
                [$paymentModel, $moduleRelation, $source] = $sourceConfig;

                return $paymentModel::query()
                    ->whereHas(
                        'jurnalKoreksi.'.$moduleRelation,
                        fn ($query) => $query->where('JwbKasusID', $jwbKasusId)
                    )
                    ->whereHas(
                        'coa',
                        fn ($query) => $query->where('JwbKasusID', $jwbKasusId)
                    )
                    ->with('jurnalKoreksi')
                    ->get()
                    ->map(fn ($payment) => (object) [
                        'sumber' => $source,
                        'keterangan' => $payment->jurnalKoreksi->Keterangan,
                        'COAID' => $payment->COAID,
                        'Debit' => $payment->Debet,
                        'Kredit' => $payment->Kredit,
                        'created_at' => $payment->jurnalKoreksi->created_at,
                    ]);
            }
        )->values();
    }

    /**
     * 2. KALKULASI LAPORAN LABA RUGI (P&L SUMMARY) LENGKAP
     * Digunakan bersama oleh:
     * - KertasKerjaService (WPL & WBS Ekuitas Saldo Laba Tahun Berjalan)
     * - BukuBesarService TOP I (Baris Saldo Laba Tahun Berjalan)
     */
    public function calculatePnlSummary(int $jwbKasusId): array
    {
        $allCoas = COA::where('JwbKasusID', $jwbKasusId)->get();
        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        $calcGroup = function (Collection $coas) use ($correctionsGrouped): array {
            $totals = [
                'PerBook' => 0,
                'AJE_RJE' => 0,
                'AuditJln' => 0,
                'AuditedBerjalan' => 0,
                'AuditSebelum' => 0,
            ];

            foreach ($coas as $coa) {
                $acc = $this->calculateAccount($coa, $this->getCorrectionsForCoa($coa, $correctionsGrouped));
                $totals['PerBook'] += $acc['PerBook'];
                $totals['AJE_RJE'] += $acc['AJE_RJE'];
                $totals['AuditJln'] += $acc['AuditedBerjalan'];
                $totals['AuditedBerjalan'] += $acc['AuditedBerjalan'];
                $totals['AuditSebelum'] += $acc['AuditSebelum'];
            }

            return $totals;
        };

        // TOP J: Penjualan / Pendapatan Usaha
        $penjualan = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Penjualan'));

        // TOP K: Beban Pokok Penjualan (HPP)
        $hpp = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Pokok Penjualan'));

        // Laba Kotor = Penjualan - HPP
        $labaKotor = [
            'PerBook' => $penjualan['PerBook'] - $hpp['PerBook'],
            'AJE_RJE' => $penjualan['AJE_RJE'] - $hpp['AJE_RJE'],
            'AuditJln' => $penjualan['AuditedBerjalan'] - $hpp['AuditedBerjalan'],
            'AuditedBerjalan' => $penjualan['AuditedBerjalan'] - $hpp['AuditedBerjalan'],
            'AuditSebelum' => $penjualan['AuditSebelum'] - $hpp['AuditSebelum'],
        ];

        // TOP L: Beban Usaha
        $bebanUsaha = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Usaha'));

        // Laba (Rugi) Usaha = Laba Kotor - Beban Usaha
        $labaUsaha = [
            'PerBook' => $labaKotor['PerBook'] - $bebanUsaha['PerBook'],
            'AJE_RJE' => $labaKotor['AJE_RJE'] - $bebanUsaha['AJE_RJE'],
            'AuditJln' => $labaKotor['AuditedBerjalan'] - $bebanUsaha['AuditedBerjalan'],
            'AuditedBerjalan' => $labaKotor['AuditedBerjalan'] - $bebanUsaha['AuditedBerjalan'],
            'AuditSebelum' => $labaKotor['AuditSebelum'] - $bebanUsaha['AuditSebelum'],
        ];

        // TOP M: Pendapatan Lain-Lain (Kredit) & Beban Lain-Lain (Debit)
        $pendLain = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Pendapatan (Beban) Lain-lain' && strtolower($c->Saldo) === 'kredit'));
        $bebanLain = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Pendapatan (Beban) Lain-lain' && strtolower($c->Saldo) === 'debit'));

        // Laba Sebelum Pajak = Laba Usaha + Pendapatan Lain - Beban Lain
        $labaSebelumPajak = [
            'PerBook' => $labaUsaha['PerBook'] + $pendLain['PerBook'] - $bebanLain['PerBook'],
            'AJE_RJE' => $labaUsaha['AJE_RJE'] + $pendLain['AJE_RJE'] - $bebanLain['AJE_RJE'],
            'AuditJln' => $labaUsaha['AuditedBerjalan'] + $pendLain['AuditedBerjalan'] - $bebanLain['AuditedBerjalan'],
            'AuditedBerjalan' => $labaUsaha['AuditedBerjalan'] + $pendLain['AuditedBerjalan'] - $bebanLain['AuditedBerjalan'],
            'AuditSebelum' => $labaUsaha['AuditSebelum'] + $pendLain['AuditSebelum'] - $bebanLain['AuditSebelum'],
        ];

        // TOP N: Beban Pajak Penghasilan
        $pajak = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Pajak Penghasilan'));

        // Laba Setelah Pajak = Laba Sebelum Pajak - Pajak Penghasilan
        $labaSetelahPajak = [
            'PerBook' => $labaSebelumPajak['PerBook'] - $pajak['PerBook'],
            'AJE_RJE' => $labaSebelumPajak['AJE_RJE'] - $pajak['AJE_RJE'],
            'AuditJln' => $labaSebelumPajak['AuditedBerjalan'] - $pajak['AuditedBerjalan'],
            'AuditedBerjalan' => $labaSebelumPajak['AuditedBerjalan'] - $pajak['AuditedBerjalan'],
            'AuditSebelum' => $labaSebelumPajak['AuditSebelum'] - $pajak['AuditSebelum'],
        ];

        return [
            'penjualan' => $penjualan,
            'hpp' => $hpp,
            'laba_kotor' => $labaKotor,
            'beban_usaha' => $bebanUsaha,
            'laba_usaha' => $labaUsaha,
            'pendapatan_lain' => $pendLain,
            'beban_lain' => $bebanLain,
            'laba_sebelum_pajak' => $labaSebelumPajak,
            'pajak' => $pajak,
            'laba_setelah_pajak' => $labaSetelahPajak,
        ];
    }

    /**
     * 3. MENGAMBIL SCHEDULE TOP BESERTA PENYESUAIAN KHUSUS TIAP TOP
     */
    public function getTopSchedule(BukuBesar $bukuBesar, ?string $subMapping = null): array
    {
        $jwbKasusId = (int) $bukuBesar->JwbKasusID;
        $mapping = $bukuBesar->Mapping;
        $isiItems = $bukuBesar->isiBukuBesar()->with('coa')->orderBy('IsiBukuBesarID')->get();
        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        if ($isiItems->isNotEmpty()) {
            $rows = $isiItems
                ->filter(fn ($item) => $item->coa !== null)
                ->filter(function ($item) use ($subMapping) {
                    $itemSubMapping = $item->SubMapping ?: ($item->coa->SubMappingTop ?: $item->coa->MappingTop);
                    return $subMapping === null || $itemSubMapping === $subMapping;
                })
                ->map(function ($item) use ($correctionsGrouped) {
                    $itemSubMapping = $item->SubMapping ?: ($item->coa->SubMappingTop ?: $item->coa->MappingTop);
                    return $this->calculateAccount(
                        $item->coa,
                        $this->getCorrectionsForCoa($item->coa, $correctionsGrouped),
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
                ->map(fn (COA $coa) => $this->calculateAccount(
                    $coa,
                    $this->getCorrectionsForCoa($coa, $correctionsGrouped)
                ))
                ->values();
        }

        // PENYESUAIAN KHUSUS TOP A: Kas dan Setara Kas
        if ($mapping === 'Kas dan Setara Kas') {
            $bankRows = $rows->filter(fn ($r) => $r['SubMapping'] === 'Rincian Setara Kas - Bank');

            if ($bankRows->isEmpty()) {
                $bankCoas = COA::where('JwbKasusID', $jwbKasusId)
                    ->where('MappingTop', 'Kas dan Setara Kas')
                    ->where('SubMappingTop', 'Rincian Setara Kas - Bank')
                    ->get();

                $bankRows = $bankCoas->map(fn ($c) => $this->calculateAccount(
                    $c,
                    $this->getCorrectionsForCoa($c, $correctionsGrouped)
                ));
            }

            $bankTotals = $this->calculateTotals($bankRows);

            $rows = $rows->map(function ($row) use ($bankTotals, $jwbKasusId) {
                if ($row['NoAkun'] === '1-0000' || strtolower(trim($row['NamaAkun'])) === 'kas di bank') {
                    $row['PerBook'] = $bankTotals['PerBook'];
                    $row['AJE_RJE'] = $bankTotals['AJE_RJE'];
                    $row['AuditJln'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditSebelum'] = $bankTotals['AuditSebelum'];
                    $row['TotalDebit'] = $bankTotals['TotalDebit'] ?? 0;
                    $row['TotalKredit'] = $bankTotals['TotalKredit'] ?? 0;
                    $row['has_corrections'] = $bankTotals['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID']);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        // PENYESUAIAN KHUSUS TOP I: Ekuitas
        if ($mapping === 'Ekuitas') {
            $pnl = $this->calculatePnlSummary($jwbKasusId);
            $labaBerjalan = $pnl['laba_setelah_pajak'];

            $rows = $rows->map(function ($row) use ($labaBerjalan, $jwbKasusId) {
                if ($row['NoAkun'] === '3-2200' || str_contains(strtolower($row['NamaAkun']), 'laba tahun berjalan')) {
                    $row['PerBook'] = $labaBerjalan['PerBook'];
                    $row['AJE_RJE'] = $labaBerjalan['AJE_RJE'];
                    $row['AuditJln'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditSebelum'] = $labaBerjalan['AuditSebelum'];
                    $row['has_corrections'] = $labaBerjalan['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID']);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        // Hitung Sub-Mappings
        $subMappings = $rows
            ->groupBy('SubMapping')
            ->map(fn (Collection $subRows, string $name) => [
                'SubMapping' => $name,
                'rows' => $subRows->values(),
                'totals' => $this->calculateTotals($subRows),
            ])
            ->values();

        // KALKULASI GRAND TOTALS SESUAI LOGIKA AKUNTANSI
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

            foreach ($rows as $r) {
                $isDebit = strtolower($r['SaldoNormal'] ?? 'kredit') === 'debit';
                $sign = $isDebit ? -1 : 1;
                $perBook += $sign * $r['PerBook'];
                $ajeRje += $sign * $r['AJE_RJE'];
                $auditedBerjalan += $sign * $r['AuditedBerjalan'];
                $auditSebelum += $sign * $r['AuditSebelum'];
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

    /**
     * 4. MENGAMBIL AKUN YANG TERSEDIA BESERTA NILAI TERKOREKSI
     */
    public function getAvailableAccounts(BukuBesar $bukuBesar): Collection
    {
        $jwbKasusId = (int) $bukuBesar->JwbKasusID;
        $mapping = $bukuBesar->Mapping;

        $coas = COA::where('JwbKasusID', $jwbKasusId)
            ->where('MappingTop', $mapping)
            ->orderBy('NoAkun')
            ->get();

        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        $rows = $coas->map(fn (COA $coa) => $this->calculateAccount(
            $coa,
            $this->getCorrectionsForCoa($coa, $correctionsGrouped)
        ));

        if ($mapping === 'Kas dan Setara Kas') {
            $bankRows = $rows->filter(fn ($r) => $r['SubMapping'] === 'Rincian Setara Kas - Bank');
            $bankTotals = $this->calculateTotals($bankRows);

            $rows = $rows->map(function ($row) use ($bankTotals, $jwbKasusId) {
                if ($row['NoAkun'] === '1-0000' || strtolower(trim($row['NamaAkun'])) === 'kas di bank') {
                    $row['PerBook'] = $bankTotals['PerBook'];
                    $row['AJE_RJE'] = $bankTotals['AJE_RJE'];
                    $row['AuditJln'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $bankTotals['AuditedBerjalan'];
                    $row['AuditSebelum'] = $bankTotals['AuditSebelum'];
                    $row['TotalDebit'] = $bankTotals['TotalDebit'] ?? 0;
                    $row['TotalKredit'] = $bankTotals['TotalKredit'] ?? 0;
                    $row['has_corrections'] = $bankTotals['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID']);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        if ($mapping === 'Ekuitas') {
            $pnl = $this->calculatePnlSummary($jwbKasusId);
            $labaBerjalan = $pnl['laba_setelah_pajak'];

            $rows = $rows->map(function ($row) use ($labaBerjalan, $jwbKasusId) {
                if ($row['NoAkun'] === '3-2200' || str_contains(strtolower($row['NamaAkun']), 'laba tahun berjalan')) {
                    $row['PerBook'] = $labaBerjalan['PerBook'];
                    $row['AJE_RJE'] = $labaBerjalan['AJE_RJE'];
                    $row['AuditJln'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditedBerjalan'] = $labaBerjalan['AuditedBerjalan'];
                    $row['AuditSebelum'] = $labaBerjalan['AuditSebelum'];
                    $row['has_corrections'] = $labaBerjalan['AJE_RJE'] !== 0;

                    $rincian = $this->calculateRincianPopUp($jwbKasusId, (int) $row['COAID']);
                    $row['rincian_jurnal'] = $rincian['rincian_jurnal'] ?? [];
                }

                return $row;
            });
        }

        return $rows->values();
    }

    public function getCorrectionsForCoa(COA $coa, Collection $correctionsGrouped): Collection
    {
        return $correctionsGrouped->get($coa->COAID, collect());
    }

    /**
     * 5. RUMUS KALKULASI SALDO NORMAL & AUDITED SATU AKUN
     */
    public function calculateAccount(COA $coa, Collection $corrections, ?string $customSubMapping = null): array
    {
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;
        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);

        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');

        // Untuk akun kontra (PerBook atau AuditSebelum bernilai minus seperti Cadangan Kerugian Piutang),
        // saldo kredit menambah nilai minus sehingga dihitung: Debit - Kredit.
        if ($perBook < 0 || $auditSebelum < 0) {
            $ajeRje = $totalDebit - $totalKredit;
        } else {
            $ajeRje = $isDebit ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
        }

        $auditedBerjalan = $perBook + $ajeRje;

        $rincian = [];
        $no = 1;

        foreach ($corrections as $c) {
            $debitVal = (int) $c->Debit;
            $kreditVal = (int) $c->Kredit;

            if ($debitVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Debit',
                    'sumber' => $c->sumber,
                    'keterangan' => $c->keterangan ?? '',
                    'nilai' => $debitVal,
                ];
            }

            if ($kreditVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Kredit',
                    'sumber' => $c->sumber,
                    'keterangan' => $c->keterangan ?? '',
                    'nilai' => $kreditVal,
                ];
            }
        }

        return [
            'COAID' => $coa->COAID,
            'NoAkun' => $coa->NoAkun,
            'NamaAkun' => $coa->NamaAkun,
            'SubMapping' => $customSubMapping ?: ($coa->SubMappingTop ?: $coa->MappingTop),
            'SaldoNormal' => $isDebit ? 'debit' : 'kredit',
            'PerBook' => $perBook,
            'AJE_RJE' => $ajeRje,
            'AuditJln' => $auditedBerjalan,
            'AuditedBerjalan' => $auditedBerjalan,
            'AuditSebelum' => $auditSebelum,
            'TotalDebit' => $totalDebit,
            'TotalKredit' => $totalKredit,
            'has_corrections' => $corrections->isNotEmpty(),
            'rincian_jurnal' => $rincian,
        ];
    }

    /**
     * 6. KALKULASI TOTAL BARIS SUB-TABEL
     */
    public function calculateTotals(iterable $rows): array
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

    /**
     * 7. KALKULASI RINCIAN MODAL POP-UP (i) ON-DEMAND
     */
    public function calculateRincianPopUp(int $jwbKasusId, int $coaId): ?array
    {
        $coa = COA::where('JwbKasusID', $jwbKasusId)
            ->where('COAID', $coaId)
            ->first();

        if (! $coa) {
            return null;
        }

        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        // Kasus spesial 1: 'Kas di Bank' (1-0000) -> ambil semua koreksi dari sub-akun Bank
        if ($coa->NoAkun === '1-0000' || strtolower(trim($coa->NamaAkun)) === 'kas di bank') {
            $bankCoaIds = COA::where('JwbKasusID', $jwbKasusId)
                ->where('MappingTop', 'Kas dan Setara Kas')
                ->where('SubMappingTop', 'Rincian Setara Kas - Bank')
                ->pluck('COAID');

            $corrections = $allCorrections->whereIn('COAID', $bankCoaIds);
        }
        // Kasus spesial 2: 'Saldo Laba Tahun Berjalan' (3-2200) -> ambil semua koreksi akun P&L (nominal)
        elseif ($coa->NoAkun === '3-2200' || str_contains(strtolower($coa->NamaAkun), 'laba tahun berjalan')) {
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
            $corrections = $this->getCorrectionsForCoa($coa, $correctionsGrouped);
        }

        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;

        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');

        if ($perBook < 0 || $auditSebelum < 0) {
            $ajeRje = $totalDebit - $totalKredit;
        } else {
            $ajeRje = $isDebit ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
        }

        $rincian = [];
        $no = 1;

        foreach ($corrections as $c) {
            $debitVal = (int) $c->Debit;
            $kreditVal = (int) $c->Kredit;

            if ($debitVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Debit',
                    'sumber' => $c->sumber,
                    'keterangan' => $c->keterangan ?? '',
                    'nilai' => $debitVal,
                ];
            }

            if ($kreditVal > 0) {
                $rincian[] = [
                    'no' => $no++,
                    'posisi' => 'Kredit',
                    'sumber' => $c->sumber,
                    'keterangan' => $c->keterangan ?? '',
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
}