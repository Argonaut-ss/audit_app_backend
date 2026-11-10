<?php

namespace App\Services;

use App\Models\AsetLain\PembayaranJurnalKoreksiAsetLain;
use App\Models\AsetTetap\PembayaranJurnalKoreksiAsetTetap;
use App\Models\BebanUsaha\PembayaranJurnalKoreksiBebanUsaha;
use App\Models\COA;
use App\Models\Kas\PembayaranJurnalKoreksiKas;
use App\Models\PendapatanUsaha\PembayaranJurnalKoreksiPendapatanUsaha;
use App\Models\Persediaan\PembayaranJurnalKoreksiPersediaan;
use App\Models\Piutang\PembayaranJurnalKoreksi;
use App\Models\SetaraKas\PembayaranJurnalKoreksiSetaraKas;
use App\Models\UtangUsaha\PembayaranJurnalKoreksiUtangUsaha;
use Illuminate\Support\Collection;

class AuditCalculationService
{
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

        $penjualan = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Penjualan'));
        $hpp = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Pokok Penjualan'));

        $labaKotor = [
            'PerBook' => $penjualan['PerBook'] - $hpp['PerBook'],
            'AJE_RJE' => $penjualan['AJE_RJE'] - $hpp['AJE_RJE'],
            'AuditJln' => $penjualan['AuditedBerjalan'] - $hpp['AuditedBerjalan'],
            'AuditedBerjalan' => $penjualan['AuditedBerjalan'] - $hpp['AuditedBerjalan'],
            'AuditSebelum' => $penjualan['AuditSebelum'] - $hpp['AuditSebelum'],
        ];

        $bebanUsaha = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Usaha'));
        $labaUsaha = [
            'PerBook' => $labaKotor['PerBook'] - $bebanUsaha['PerBook'],
            'AJE_RJE' => $labaKotor['AJE_RJE'] - $bebanUsaha['AJE_RJE'],
            'AuditJln' => $labaKotor['AuditedBerjalan'] - $bebanUsaha['AuditedBerjalan'],
            'AuditedBerjalan' => $labaKotor['AuditedBerjalan'] - $bebanUsaha['AuditedBerjalan'],
            'AuditSebelum' => $labaKotor['AuditSebelum'] - $bebanUsaha['AuditSebelum'],
        ];

        $pendLain = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Pendapatan (Beban) Lain-lain' && strtolower($c->Saldo) === 'kredit'));
        $bebanLain = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Pendapatan (Beban) Lain-lain' && strtolower($c->Saldo) === 'debit'));
        $labaSebelumPajak = [
            'PerBook' => $labaUsaha['PerBook'] + $pendLain['PerBook'] - $bebanLain['PerBook'],
            'AJE_RJE' => $labaUsaha['AJE_RJE'] + $pendLain['AJE_RJE'] - $bebanLain['AJE_RJE'],
            'AuditJln' => $labaUsaha['AuditedBerjalan'] + $pendLain['AuditedBerjalan'] - $bebanLain['AuditedBerjalan'],
            'AuditedBerjalan' => $labaUsaha['AuditedBerjalan'] + $pendLain['AuditedBerjalan'] - $bebanLain['AuditedBerjalan'],
            'AuditSebelum' => $labaUsaha['AuditSebelum'] + $pendLain['AuditSebelum'] - $bebanLain['AuditSebelum'],
        ];

        $pajak = $calcGroup($allCoas->filter(fn ($c) => $c->MappingTop === 'Beban Pajak Penghasilan'));
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

    public function getCorrectionsForCoa(COA $coa, Collection $correctionsGrouped): Collection
    {
        return $correctionsGrouped->get($coa->COAID, collect());
    }

    public function calculateAccount(COA $coa, Collection $corrections, ?string $customSubMapping = null): array
    {
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;
        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);

        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');

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
}
