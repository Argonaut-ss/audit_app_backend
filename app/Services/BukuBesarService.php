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

    public function getTopSchedule(BukuBesar $bukuBesar, ?string $subMapping = null): array {
        $isiItems = $bukuBesar->isiBukuBesar()->with('coa')->orderBy('IsiBukuBesarID')->get();
        $allCorrections = $this->getAggregatedCorrections((int) $bukuBesar->JwbKasusID);
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
            $coas = COA::where('JwbKasusID', $bukuBesar->JwbKasusID)
                ->where('MappingTop', $bukuBesar->Mapping)
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

        $subMappings = $rows
            ->groupBy('SubMapping')
            ->map(fn (Collection $subRows, string $name) => [
                'SubMapping' => $name,
                'rows' => $subRows->values(),
                'totals' => $this->calculateTotals($subRows),
            ])
            ->values();

        return [
            'sub_mappings' => $subMappings,
            'totals' => $this->calculateTotals($rows),
        ];
    }

    public function getAvailableAccounts(BukuBesar $bukuBesar): Collection
    {
        $coas = COA::where('JwbKasusID', $bukuBesar->JwbKasusID)
            ->where('MappingTop', $bukuBesar->Mapping)
            ->orderBy('NoAkun')
            ->get();

        $allCorrections = $this->getAggregatedCorrections((int) $bukuBesar->JwbKasusID);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        return $coas->map(fn (COA $coa) => $this->calculateAccount(
            $coa,
            $this->getCorrectionsForCoa($coa, $correctionsGrouped)
        ))->values();
    }

    public function getCorrectionsForCoa(COA $coa, Collection $correctionsGrouped): Collection {
        return $correctionsGrouped->get($coa->COAID, collect());
    }

    /**
     * 3. RUMUS KALKULASI SALDO NORMAL & AUDITED SATU AKUN
     * Menerapkan aturan:
     * - Akun Debit  (Aset, Beban)          -> AJE = Debit - Kredit
     * - Akun Kredit (Liabilitas, Ekuitas, Pendapatan) -> AJE = Kredit - Debit
     * - Audited Berjalan = PerBook + AJE
     */
    public function calculateAccount(COA $coa, Collection $corrections, ?string $customSubMapping = null): array
    {
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;
        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);

        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');

        $ajeRje = $isDebit ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
        $auditedBerjalan = $perBook + $ajeRje;

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
        ];
    }

    /**
     * 4. KALKULASI DAFTAR AKUN SEKALIGUS
     * Dipanggil oleh Controller dengan mengirimkan daftar akun COA yang ingin dihitung.
     * Mengembalikan Collection akun yang sudah dilengkapi hasil perhitungan finansial.
     */
    public function calculateAccounts(int $jwbKasusId, Collection $coas): Collection
    {
        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        return $coas->map(function (COA $coa) use ($correctionsGrouped) {
            $corrections = $this->getCorrectionsForCoa($coa, $correctionsGrouped);

            return $this->calculateAccount($coa, $corrections);
        });
    }

    /**
     * 5. KALKULASI TOTAL BARIS SUB-TABEL
     * Helper murni untuk menjumlahkan baris-baris akun pada sub-tabel.
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
     * 6. KALKULASI RINCIAN MODAL POP-UP (i) ON-DEMAND
     * Dipanggil oleh Controller hanya saat user mengklik tanda info (i) pada satu akun tertentu.
     * Menghitung total mutasi dan memecah rincian transaksi Debit/Kredit untuk akun tersebut.
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

        $corrections = $this->getCorrectionsForCoa($coa, $correctionsGrouped);

        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);

        $totalDebit = (int) $corrections->sum('Debit');
        $totalKredit = (int) $corrections->sum('Kredit');
        $ajeRje = $isDebit ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);

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
            'TotalDebit' => $totalDebit,
            'TotalKredit' => $totalKredit,
            'AJE_RJE' => $ajeRje,
            'rincian_jurnal' => $rincian,
        ];
    }
}
