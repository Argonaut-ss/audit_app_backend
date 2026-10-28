<?php

namespace App\Services;

use App\Models\COA;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BukuBesarService
{
    /**
     * Pemetaan huruf TOP A s/d TOP N ke nama siklus audit.
     */
    public const TOP_MAPPING = [
        'A' => 'Kas dan Setara Kas',
        'B' => 'Piutang',
        'C' => 'Persediaan',
        'D' => 'Aset Lain-Lain',
        'E' => 'Aset Tetap',
        'F' => 'Utang Usaha',
        'G' => 'Utang Bank',
        'H' => 'Utang Pajak',
        'I' => 'Ekuitas',
        'J' => 'Penjualan',
        'K' => 'Beban Pokok Penjualan',
        'L' => 'Beban Usaha',
        'M' => 'Pendapatan (Beban) Lain-lain',
        'N' => 'Beban Pajak Penghasilan',
    ];

    /**
     * 1. QUERY AGREGASI 9 MODUL SUBSTANTIF (Single-Trip UNION ALL)
     * Mengambil seluruh baris koreksi untuk 1 kasus mahasiswa dalam 1 kali query database cepat.
     */
    public function getAggregatedCorrections(int $jwbKasusId): Collection
    {
        $sql = "
            SELECT 'Kas dan Setara Kas' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_setara_kas p
            JOIN jurnal_koreksi_setara_kas jk ON jk.JurnalKoreksiSetaraKasID = p.JurnalKoreksiSetaraKasID
            JOIN setara_kas s ON s.SetaraKasID = jk.SetaraKasID
            WHERE s.JwbKasusID = ?

            UNION ALL

            SELECT 'Kas' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_kas p
            JOIN jurnal_koreksi_kas jk ON jk.JurnalKoreksiKasID = p.JurnalKoreksiKasID
            JOIN kas k ON k.KasID = jk.KasID
            WHERE k.JwbKasusID = ?

            UNION ALL

            SELECT 'Piutang' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM PembayaranJurnalKoreksi p
            JOIN JurnalKoreksi jk ON jk.JurnalKoreksiID = p.JurnalKoreksiID
            JOIN piutang pt ON pt.PiutangID = jk.PiutangID
            WHERE pt.JwbKasusID = ?

            UNION ALL

            SELECT 'Utang Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_utang_usaha p
            JOIN jurnal_koreksi_utang_usaha jk ON jk.JurnalKoreksiUtangUsahaID = p.JurnalKoreksiUtangUsahaID
            JOIN utang_usaha u ON u.UtangUsahaID = jk.UtangUsahaID
            WHERE u.JwbKasusID = ?

            UNION ALL

            SELECT 'Persediaan' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_persediaan p
            JOIN jurnal_koreksi_persediaan jk ON jk.JurnalKoreksiPersediaanID = p.JurnalKoreksiPersediaanID
            JOIN persediaan ps ON ps.PersediaanID = jk.PersediaanID
            WHERE ps.JwbKasusID = ?

            UNION ALL

            SELECT 'Aset Tetap' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_aset_tetap p
            JOIN jurnal_koreksi_aset_tetap jk ON jk.JurnalKoreksiAsetTetapID = p.JurnalKoreksiAsetTetapID
            JOIN aset_tetap at ON at.AsetTetapID = jk.AsetTetapID
            WHERE at.JwbKasusID = ?

            UNION ALL

            SELECT 'Aset Lain-Lain' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_aset_lain p
            JOIN jurnal_koreksi_aset_lain jk ON jk.JurnalKoreksiAsetLainID = p.JurnalKoreksiAsetLainID
            JOIN aset_lain al ON al.AsetLainID = jk.AsetLainID
            WHERE al.JwbKasusID = ?

            UNION ALL

            SELECT 'Pendapatan Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_pendapatan_usaha p
            JOIN jurnal_koreksi_pendapatan_usaha jk ON jk.JurnalKoreksiPendapatanUsahaID = p.JurnalKoreksiPendapatanUsahaID
            JOIN pendapatan_usaha pu ON pu.PendapatanUsahaID = jk.PendapatanUsahaID
            WHERE pu.JwbKasusID = ?

            UNION ALL

            SELECT 'Beban Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet AS Debit, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_beban_usaha p
            JOIN jurnal_koreksi_beban_usaha jk ON jk.JurnalKoreksiBebanUsahaID = p.JurnalKoreksiBebanUsahaID
            JOIN beban_usaha bu ON bu.BebanUsahaID = jk.BebanUsahaID
            WHERE bu.JwbKasusID = ?
        ";

        $bindings = array_fill(0, 9, $jwbKasusId);
        $results = DB::select($sql, $bindings);

        return collect($results);
    }

    /**
     * 2. LOGIKA ROLLUP MUTASI AKUN INDUK (KONTROL)
     * Jika akun adalah induk seperti 1-0000 Kas di Bank, gabungkan mutasi rekening bank anaknya.
     */
    public function getCorrectionsForCoa(
        COA $coa,
        Collection $allCorrections,
        Collection $correctionsGrouped,
        Collection $allCoasInCycle
    ): Collection {
        if ($coa->NoAkun === '1-0000' || $coa->NoAkun === '1000') {
            $bankCoaIds = $allCoasInCycle
                ->filter(fn ($c) => $c->SubMappingTop === 'Rincian Setara Kas - Bank' || str_starts_with((string) $c->NoAkun, '1-111'))
                ->pluck('COAID');

            if ($bankCoaIds->isNotEmpty()) {
                return $allCorrections->whereIn('COAID', $bankCoaIds);
            }
        }

        return $correctionsGrouped->get($coa->COAID, collect());
    }

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
