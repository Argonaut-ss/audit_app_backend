<?php

namespace App\Services;

use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Support\Collection;

class KertasKerjaService
{
    protected BukuBesarService $bukuBesarService;

    public function __construct(BukuBesarService $bukuBesarService)
    {
        $this->bukuBesarService = $bukuBesarService;
    }

    public function getKertasKerja(JwbKasus $jwbKasus): array
    {
        $jwbKasusId = (int) $jwbKasus->JwbKasusID;
        $allCoas = COA::where('JwbKasusID', $jwbKasusId)->get();
        $allCorrections = $this->bukuBesarService->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        // Helper perhitungan grup COA
        $calc = function (Collection $coas) use ($correctionsGrouped): array {
            $totals = [
                'saldo_per_book' => 0,
                'aje_rje' => 0,
                'saldo_audit_berjalan' => 0,
                'saldo_audit_sebelum' => 0,
            ];

            foreach ($coas as $coa) {
                $acc = $this->bukuBesarService->calculateAccount($coa, $this->bukuBesarService->getCorrectionsForCoa($coa, $correctionsGrouped));
                $totals['saldo_per_book'] += $acc['PerBook'];
                $totals['aje_rje'] += $acc['AJE_RJE'];
                $totals['saldo_audit_berjalan'] += $acc['AuditedBerjalan'];
                $totals['saldo_audit_sebelum'] += $acc['AuditSebelum'];
            }

            return $totals;
        };

        // 1. AMBIL SUMMARY P&L DARI BUKUBESARSERVICE (Single Source of Truth)
        $pnl = $this->bukuBesarService->calculatePnlSummary($jwbKasusId);

        $penjualanTotals = $this->toKertasKerjaFormat($pnl['penjualan']);
        $hppTotals = $this->toKertasKerjaFormat($pnl['hpp']);
        $labaKotorTotals = $this->toKertasKerjaFormat($pnl['laba_kotor']);
        $bebanUsahaTotals = $this->toKertasKerjaFormat($pnl['beban_usaha']);
        $labaUsahaTotals = $this->toKertasKerjaFormat($pnl['laba_usaha']);
        $pendLainTotals = $this->toKertasKerjaFormat($pnl['pendapatan_lain']);
        $bebanLainTotals = $this->toKertasKerjaFormat($pnl['beban_lain']);
        $labaSebelumPajakTotals = $this->toKertasKerjaFormat($pnl['laba_sebelum_pajak']);
        $pajakTotals = $this->toKertasKerjaFormat($pnl['pajak']);
        $labaSetelahPajakTotals = $this->toKertasKerjaFormat($pnl['laba_setelah_pajak']);

        // 2. KALKULASI KELOMPOK NERACA (WBS)
        // TOP A: Kas dan Setara Kas (Kas Kecil + Rincian Bank, exclude akun header induk 1-0000)
        $kasTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Kas dan Setara Kas' && $c->NoAkun !== '1-0000'));

        // TOP B: Piutang
        $piutangTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Piutang'));

        // TOP C: Persediaan
        $persediaanTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Persediaan'));

        // TOP D: Aset Lain-Lain
        $asetLainTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Aset Lain-Lain'));

        // TOP E: Aset Tetap (Debit - Kredit karena akumulasi depresiasi adalah kontra aset)
        $atDeb = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Aset Tetap' && strtolower($c->Saldo) === 'debit'));
        $atKred = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Aset Tetap' && strtolower($c->Saldo) === 'kredit'));
        $asetTetapTotals = [
            'saldo_per_book' => $atDeb['saldo_per_book'] - $atKred['saldo_per_book'],
            'aje_rje' => $atDeb['aje_rje'] - $atKred['aje_rje'],
            'saldo_audit_berjalan' => $atDeb['saldo_audit_berjalan'] - $atKred['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $atDeb['saldo_audit_sebelum'] - $atKred['saldo_audit_sebelum'],
        ];

        // TOP F: Utang Usaha
        $utangUsahaTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Utang Usaha'));

        // TOP G: Utang Bank
        $utangBankTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Utang Bank'));

        // TOP H: Utang Pajak
        $utangPajakTotals = $calc($allCoas->filter(fn ($c) => $c->MappingTop === 'Utang Pajak'));

        // TOP I: Ekuitas
        $saldoLabaCoa = $allCoas->first(fn ($c) => $c->NoAkun === '3-2100' || $c->NamaAkun === 'Saldo Laba');
        $modalSahamCoa = $allCoas->first(fn ($c) => $c->NoAkun === '3-1100' || $c->NamaAkun === 'Modal Saham');
        $dividenCoa = $allCoas->first(fn ($c) => $c->NoAkun === '3-1200' || $c->NamaAkun === 'Dividen');

        $saldoLabaTotals = $saldoLabaCoa ? $calc(collect([$saldoLabaCoa])) : $this->emptyRow();
        $modalSahamTotals = $modalSahamCoa ? $calc(collect([$modalSahamCoa])) : $this->emptyRow();
        $dividenTotals = $dividenCoa ? $calc(collect([$dividenCoa])) : $this->emptyRow();

        // Saldo Laba Tahun Berjalan di WBS mirrors Laba Setelah Pajak WPL
        $saldoLabaTahunBerjalanTotals = $labaSetelahPajakTotals;

        // TOTAL-TOTAL WBS
        $totalAsetLancarTotals = [
            'saldo_per_book' => $kasTotals['saldo_per_book'] + $piutangTotals['saldo_per_book'] + $persediaanTotals['saldo_per_book'] + $asetLainTotals['saldo_per_book'],
            'aje_rje' => $kasTotals['aje_rje'] + $piutangTotals['aje_rje'] + $persediaanTotals['aje_rje'] + $asetLainTotals['aje_rje'],
            'saldo_audit_berjalan' => $kasTotals['saldo_audit_berjalan'] + $piutangTotals['saldo_audit_berjalan'] + $persediaanTotals['saldo_audit_berjalan'] + $asetLainTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $kasTotals['saldo_audit_sebelum'] + $piutangTotals['saldo_audit_sebelum'] + $persediaanTotals['saldo_audit_sebelum'] + $asetLainTotals['saldo_audit_sebelum'],
        ];

        $totalAsetTotals = [
            'saldo_per_book' => $totalAsetLancarTotals['saldo_per_book'] + $asetTetapTotals['saldo_per_book'],
            'aje_rje' => $totalAsetLancarTotals['aje_rje'] + $asetTetapTotals['aje_rje'],
            'saldo_audit_berjalan' => $totalAsetLancarTotals['saldo_audit_berjalan'] + $asetTetapTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $totalAsetLancarTotals['saldo_audit_sebelum'] + $asetTetapTotals['saldo_audit_sebelum'],
        ];

        $totalLiabilitasLancarTotals = [
            'saldo_per_book' => $utangUsahaTotals['saldo_per_book'] + $utangPajakTotals['saldo_per_book'],
            'aje_rje' => $utangUsahaTotals['aje_rje'] + $utangPajakTotals['aje_rje'],
            'saldo_audit_berjalan' => $utangUsahaTotals['saldo_audit_berjalan'] + $utangPajakTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $utangUsahaTotals['saldo_audit_sebelum'] + $utangPajakTotals['saldo_audit_sebelum'],
        ];

        $totalLiabilitasTotals = [
            'saldo_per_book' => $totalLiabilitasLancarTotals['saldo_per_book'] + $utangBankTotals['saldo_per_book'],
            'aje_rje' => $totalLiabilitasLancarTotals['aje_rje'] + $utangBankTotals['aje_rje'],
            'saldo_audit_berjalan' => $totalLiabilitasLancarTotals['saldo_audit_berjalan'] + $utangBankTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $totalLiabilitasLancarTotals['saldo_audit_sebelum'] + $utangBankTotals['saldo_audit_sebelum'],
        ];

        $totalEkuitasTotals = [
            'saldo_per_book' => $saldoLabaTotals['saldo_per_book'] + $modalSahamTotals['saldo_per_book'] - $dividenTotals['saldo_per_book'] + $saldoLabaTahunBerjalanTotals['saldo_per_book'],
            'aje_rje' => $saldoLabaTotals['aje_rje'] + $modalSahamTotals['aje_rje'] - $dividenTotals['aje_rje'] + $saldoLabaTahunBerjalanTotals['aje_rje'],
            'saldo_audit_berjalan' => $saldoLabaTotals['saldo_audit_berjalan'] + $modalSahamTotals['saldo_audit_berjalan'] - $dividenTotals['saldo_audit_berjalan'] + $saldoLabaTahunBerjalanTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $saldoLabaTotals['saldo_audit_sebelum'] + $modalSahamTotals['saldo_audit_sebelum'] - $dividenTotals['saldo_audit_sebelum'] + $saldoLabaTahunBerjalanTotals['saldo_audit_sebelum'],
        ];

        $totalLiabilitasDanEkuitasTotals = [
            'saldo_per_book' => $totalLiabilitasTotals['saldo_per_book'] + $totalEkuitasTotals['saldo_per_book'],
            'aje_rje' => $totalLiabilitasTotals['aje_rje'] + $totalEkuitasTotals['aje_rje'],
            'saldo_audit_berjalan' => $totalLiabilitasTotals['saldo_audit_berjalan'] + $totalEkuitasTotals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => $totalLiabilitasTotals['saldo_audit_sebelum'] + $totalEkuitasTotals['saldo_audit_sebelum'],
        ];

        // 3. SUSUN JSON SESUAI FORMAT RESMI
        $wpl = [
            [
                'akun_utama' => 'WPL',
                'data' => [
                    $this->buildItem('Pendapatan Usaha', 'Pendapatan Usaha', $penjualanTotals),
                    $this->buildItem('HPP', 'HPP', $hppTotals),
                    $this->buildItem('Laba Kotor', 'Laba Kotor', $labaKotorTotals, true),
                    $this->buildItem('Beban Usaha', 'Beban Usaha', $bebanUsahaTotals),
                    $this->buildItem('Laba (Rugi) Usaha', 'Laba (Rugi) Usaha', $labaUsahaTotals, true),
                    $this->buildItem('Pendapatan Lain-Lain', 'Pendapatan Lain-Lain', $pendLainTotals),
                    $this->buildItem('Beban Lain-Lain', 'Beban Lain-Lain', $bebanLainTotals),
                    $this->buildItem('Laba Bersih Sebelum Pajak', 'Laba Bersih Sebelum Pajak', $labaSebelumPajakTotals, true),
                    $this->buildItem('Pajak Penghasilan', 'Pajak Penghasilan', $pajakTotals),
                    $this->buildItem('Laba Bersih Setelah Pajak', 'Laba Bersih Setelah Pajak', $labaSetelahPajakTotals, true),
                ],
            ],
        ];

        $wbs = [
            [
                'akun_utama' => 'Aset',
                'data' => [
                    [
                        'sub_akun' => 'Aset Lancar',
                        'detail' => [
                            $this->buildDetail('Kas dan Setara Kas', 'Aset Lancar', $kasTotals),
                            $this->buildDetail('Piutang', 'Aset Lancar', $piutangTotals),
                            $this->buildDetail('Persediaan', 'Aset Lancar', $persediaanTotals),
                            $this->buildDetail('Aset Lain-Lain', 'Aset Lancar', $asetLainTotals),
                            $this->buildDetail('Total Aset Lancar', 'Total Aset Lancar', $totalAsetLancarTotals),
                        ],
                    ],
                    [
                        'sub_akun' => 'Aset Tidak Lancar',
                        'detail' => [
                            $this->buildDetail('Aset Tetap', 'Aset Tidak Lancar', $asetTetapTotals),
                            $this->buildDetail('Total Aset Tidak Lancar', 'Total Aset Tidak Lancar', $asetTetapTotals),
                        ],
                    ],
                ],
            ],
            [
                'akun_utama' => 'Total Aset',
                'data' => [
                    [
                        'sub_akun' => '',
                        'detail' => [
                            $this->buildDetail('Total Aset', 'Total Aset', $totalAsetTotals),
                        ],
                    ],
                ],
            ],
            [
                'akun_utama' => 'Liabilitas',
                'data' => [
                    [
                        'sub_akun' => 'Liabilitas Jangka Pendek',
                        'detail' => [
                            $this->buildDetail('Utang Usaha', 'Liabilitas Jangka Pendek', $utangUsahaTotals),
                            $this->buildDetail('Utang Pajak', 'Liabilitas Jangka Pendek', $utangPajakTotals),
                            $this->buildDetail('Total Liabilitas Jangka Pendek', 'Total Liabilitas Jangka Pendek', $totalLiabilitasLancarTotals),
                        ],
                    ],
                    [
                        'sub_akun' => 'Liabilitas Jangka Panjang',
                        'detail' => [
                            $this->buildDetail('Utang Bank', 'Liabilitas Jangka Panjang', $utangBankTotals),
                            $this->buildDetail('Total Liabilitas Jangka Panjang', 'Total Liabilitas Jangka Panjang', $utangBankTotals),
                        ],
                    ],
                ],
            ],
            [
                'akun_utama' => 'Total Liabilitas',
                'data' => [
                    [
                        'sub_akun' => '',
                        'detail' => [
                            $this->buildDetail('Total Liabilitas', 'Total Liabilitas', $totalLiabilitasTotals),
                        ],
                    ],
                ],
            ],
            [
                'akun_utama' => 'Ekuitas',
                'data' => [
                    [
                        'sub_akun' => 'Ekuitas',
                        'detail' => [
                            $this->buildDetail('Saldo Laba', 'Ekuitas', $saldoLabaTotals),
                            $this->buildDetail('Modal Saham', 'Ekuitas', $modalSahamTotals),
                            $this->buildDetail('Dividen', 'Ekuitas', $dividenTotals),
                            $this->buildDetail('Saldo Laba Tahun Berjalan', 'Ekuitas', $saldoLabaTahunBerjalanTotals),
                            $this->buildDetail('Total Ekuitas', 'Total Ekuitas', $totalEkuitasTotals),
                        ],
                    ],
                ],
            ],
            [
                'akun_utama' => 'Total Liabilitas dan Ekuitas',
                'data' => [
                    [
                        'sub_akun' => '',
                        'detail' => [
                            $this->buildDetail('Total Liabilitas dan Ekuitas', 'Total Liabilitas dan Ekuitas', $totalLiabilitasDanEkuitasTotals),
                        ],
                    ],
                ],
            ],
        ];

        return [
            'wpl' => $wpl,
            'wbs' => $wbs,
        ];
    }

    private function toKertasKerjaFormat(array $totals): array
    {
        return [
            'saldo_per_book' => (int) ($totals['PerBook'] ?? 0),
            'aje_rje' => (int) ($totals['AJE_RJE'] ?? 0),
            'saldo_audit_berjalan' => (int) ($totals['AuditedBerjalan'] ?? 0),
            'saldo_audit_sebelum' => (int) ($totals['AuditSebelum'] ?? 0),
        ];
    }

    private function buildItem(string $subAkun, string $keterangan, array $totals, bool $isSummary = false): array
    {
        $item = [
            'sub_akun' => $subAkun,
            'detail' => [
                $this->buildDetail($keterangan, $subAkun, $totals),
            ],
        ];

        if ($isSummary) {
            $item['is_income_summary'] = true;
        }

        return $item;
    }

    private function buildDetail(string $keterangan, string $subAkun, array $totals): array
    {
        return [
            'keterangan' => $keterangan,
            'sub_akun' => $subAkun,
            'saldo_per_book' => (int) $totals['saldo_per_book'],
            'aje/rje' => (int) $totals['aje_rje'],
            'saldo_audit_berjalan' => (int) $totals['saldo_audit_berjalan'],
            'saldo_audit_sebelum' => (int) $totals['saldo_audit_sebelum'],
        ];
    }

    private function emptyRow(): array
    {
        return [
            'saldo_per_book' => 0,
            'aje_rje' => 0,
            'saldo_audit_berjalan' => 0,
            'saldo_audit_sebelum' => 0,
        ];
    }
}
