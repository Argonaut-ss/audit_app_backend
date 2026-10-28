<?php

namespace App\Services;

use App\Models\COA;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BukuBesarService
{
    /**
     * 3. RUMUS KALKULASI SALDO NORMAL & AUDITED SATU AKUN
     * Menerapkan aturan:
     * - Akun Debit  (Aset, Beban)          -> AJE = Debit - Kredit
     * - Akun Kredit (Liabilitas, Ekuitas, Pendapatan) -> AJE = Kredit - Debit
     * - Audited Berjalan = PerBook + AJE
     */
    public function calculateAccount(COA $coa, Collection $corrections): array
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
            'SubMapping' => $coa->SubMappingTop ?: $coa->MappingTop,
            'SaldoNormal' => $isDebit ? 'debit' : 'kredit',
            'PerBook' => $perBook,
            'AJE_RJE' => $ajeRje,
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

        return $coas->map(function (COA $coa) use ($allCorrections, $correctionsGrouped, $coas) {
            $corrections = $this->getCorrectionsForCoa($coa, $allCorrections, $correctionsGrouped, $coas);
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

        $allCoasInCycle = COA::where('JwbKasusID', $jwbKasusId)
            ->where('MappingTop', $coa->MappingTop)
            ->get();

        $allCorrections = $this->getAggregatedCorrections($jwbKasusId);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        $corrections = $this->getCorrectionsForCoa($coa, $allCorrections, $correctionsGrouped, $allCoasInCycle);

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
