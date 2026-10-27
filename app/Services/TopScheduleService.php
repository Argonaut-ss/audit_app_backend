<?php

namespace App\Services;

use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\TopSchedule\TopSchedule;
use App\Models\TopSchedule\TopScheduleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopScheduleService
{
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
     * Mengambil seluruh baris jurnal koreksi dari 9 modul pengujian substantif
     * untuk satu JwbKasusID dalam 1 kali query database (Single-Trip UNION ALL).
     */
    public function getAggregatedCorrections(int $jwbKasusId): Collection
    {
        $sql = "
            SELECT 'Kas dan Setara Kas' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_setara_kas p
            JOIN jurnal_koreksi_setara_kas jk ON jk.JurnalKoreksiSetaraKasID = p.JurnalKoreksiSetaraKasID
            JOIN setara_kas s ON s.SetaraKasID = jk.SetaraKasID
            WHERE s.JwbKasusID = ?

            UNION ALL

            SELECT 'Kas' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_kas p
            JOIN jurnal_koreksi_kas jk ON jk.JurnalKoreksiKasID = p.JurnalKoreksiKasID
            JOIN kas k ON k.KasID = jk.KasID
            WHERE k.JwbKasusID = ?

            UNION ALL

            SELECT 'Piutang' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM PembayaranJurnalKoreksi p
            JOIN JurnalKoreksi jk ON jk.JurnalKoreksiID = p.JurnalKoreksiID
            JOIN piutang pt ON pt.PiutangID = jk.PiutangID
            WHERE pt.JwbKasusID = ?

            UNION ALL

            SELECT 'Utang Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_utang_usaha p
            JOIN jurnal_koreksi_utang_usaha jk ON jk.JurnalKoreksiUtangUsahaID = p.JurnalKoreksiUtangUsahaID
            JOIN utang_usaha u ON u.UtangUsahaID = jk.UtangUsahaID
            WHERE u.JwbKasusID = ?

            UNION ALL

            SELECT 'Persediaan' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_persediaan p
            JOIN jurnal_koreksi_persediaan jk ON jk.JurnalKoreksiPersediaanID = p.JurnalKoreksiPersediaanID
            JOIN persediaan ps ON ps.PersediaanID = jk.PersediaanID
            WHERE ps.JwbKasusID = ?

            UNION ALL

            SELECT 'Aset Tetap' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_aset_tetap p
            JOIN jurnal_koreksi_aset_tetap jk ON jk.JurnalKoreksiAsetTetapID = p.JurnalKoreksiAsetTetapID
            JOIN aset_tetap at ON at.AsetTetapID = jk.AsetTetapID
            WHERE at.JwbKasusID = ?

            UNION ALL

            SELECT 'Aset Lain-Lain' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_aset_lain p
            JOIN jurnal_koreksi_aset_lain jk ON jk.JurnalKoreksiAsetLainID = p.JurnalKoreksiAsetLainID
            JOIN aset_lain al ON al.AsetLainID = jk.AsetLainID
            WHERE al.JwbKasusID = ?

            UNION ALL

            SELECT 'Pendapatan Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
            FROM pembayaran_jurnal_koreksi_pendapatan_usaha p
            JOIN jurnal_koreksi_pendapatan_usaha jk ON jk.JurnalKoreksiPendapatanUsahaID = p.JurnalKoreksiPendapatanUsahaID
            JOIN pendapatan_usaha pu ON pu.PendapatanUsahaID = jk.PendapatanUsahaID
            WHERE pu.JwbKasusID = ?

            UNION ALL

            SELECT 'Beban Usaha' AS sumber, jk.Keterangan AS keterangan, p.COAID, p.Debet, p.Kredit, jk.created_at
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
     * Memformat baris akun beserta kalkulasi AJE/RJE dan rincian jurnal untuk modal detail (i).
     */
    public function formatAccountRow(COA $coa, Collection $corrections): array
    {
        $perBook = (int) $coa->PerBook;
        $auditSebelum = (int) $coa->AuditSebelum;
        $saldoNormal = strtolower(trim($coa->Saldo ?? 'debit'));
        $isDebit = in_array($saldoNormal, ['debit', 'debet']);

        $totalDebet = (int) $corrections->sum('Debet');
        $totalKredit = (int) $corrections->sum('Kredit');

        $ajeRje = $isDebit ? ($totalDebet - $totalKredit) : ($totalKredit - $totalDebet);
        $auditedBerjalan = $perBook + $ajeRje;

        // Siapkan rincian jurnal per transaksi untuk modal detail (i)
        $rincianJurnal = [];
        $no = 1;
        foreach ($corrections as $c) {
            $debetVal = (int) $c->Debet;
            $kreditVal = (int) $c->Kredit;

            if ($debetVal > 0) {
                $rincianJurnal[] = [
                    'no' => $no++,
                    'posisi' => 'Debit',
                    'sumber' => $c->sumber,
                    'keterangan' => $c->keterangan ?? '',
                    'nilai' => $debetVal,
                ];
            }

            if ($kreditVal > 0) {
                $rincianJurnal[] = [
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
            'SubMapping' => $coa->SubMappingTop ?? $coa->MappingTop,
            'SaldoNormal' => $isDebit ? 'debit' : 'kredit',
            'PerBook' => $perBook,
            'AJE_RJE' => $ajeRje,
            'AuditedBerjalan' => $auditedBerjalan,
            'AuditSebelum' => $auditSebelum,
            'TotalDebit' => $totalDebet,
            'TotalKredit' => $totalKredit,
            'rincian_jurnal' => $rincianJurnal,
        ];
    }

    /**
     * Mengambil kumpulan koreksi untuk akun spesifik, termasuk rollup agregat
     * untuk akun induk (kontrol) seperti 1-0000 Kas di Bank.
     */
    protected function getCorrectionsForCoa(
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
     * Mengambil data lengkap satu Top Schedule (A s/d N) untuk satu JwbKasus.
     */
    public function getTopScheduleData(JwbKasus $jwbKasus, string $topLetter = 'A'): array
    {
        $topLetter = strtoupper(trim($topLetter));
        $mappingName = self::TOP_MAPPING[$topLetter] ?? self::TOP_MAPPING['A'];

        $periodeStr = $jwbKasus->Periode ?? '2022-12-31';
        $defaultAuditSebelum = date('Y-m-d', strtotime($periodeStr . ' -1 year'));

        // Ambil atau buat record master TopSchedule
        $topSchedule = TopSchedule::firstOrCreate(
            [
                'JwbKasusID' => $jwbKasus->JwbKasusID,
                'Top' => $topLetter,
            ],
            [
                'Mapping' => $mappingName,
                'TanggalPerBook' => $periodeStr,
                'TanggalAuditBerjalan' => $periodeStr,
                'TanggalAuditSebelum' => $defaultAuditSebelum,
            ]
        );

        if (! $topSchedule->TanggalAuditSebelum) {
            $topSchedule->TanggalAuditSebelum = $defaultAuditSebelum;
            $topSchedule->saveQuietly();
        }

        // Ambil akun COA master yang relevan dengan mapping siklus ini
        $allCoasInCycle = COA::where('JwbKasusID', $jwbKasus->JwbKasusID)
            ->where('MappingTop', $mappingName)
            ->orderBy('NoAkun')
            ->get();

        // Ambil seluruh koreksi dari 9 modul (Single-trip query)
        $allCorrections = $this->getAggregatedCorrections($jwbKasus->JwbKasusID);
        $correctionsGrouped = $allCorrections->groupBy('COAID');

        // Cek apakah mahasiswa sudah memiliki susunan item tersimpan
        $savedItems = $topSchedule->items()->with('coa')->get();

        if ($savedItems->isEmpty()) {
            // Default inisialisasi: gunakan seluruh COA master yang ada di siklus ini
            $activeRows = $allCoasInCycle->map(function (COA $coa) use ($allCorrections, $correctionsGrouped, $allCoasInCycle) {
                $corrections = $this->getCorrectionsForCoa($coa, $allCorrections, $correctionsGrouped, $allCoasInCycle);
                $formatted = $this->formatAccountRow($coa, $corrections);
                $formatted['SubMapping'] = $coa->SubMappingTop ?: $coa->MappingTop;

                return $formatted;
            });
        } else {
            // Gunakan susunan item yang disimpan mahasiswa
            $activeRows = $savedItems->map(function (TopScheduleItem $item) use ($allCorrections, $correctionsGrouped, $allCoasInCycle) {
                $coa = $item->coa;
                if (! $coa) {
                    return null;
                }
                $corrections = $this->getCorrectionsForCoa($coa, $allCorrections, $correctionsGrouped, $allCoasInCycle);
                $formatted = $this->formatAccountRow($coa, $corrections);
                $formatted['SubMapping'] = $item->SubMapping ?: ($coa->SubMappingTop ?: $coa->MappingTop);

                return $formatted;
            })->filter()->values();
        }

        // Kelompokkan baris berdasarkan SubMapping (untuk membentuk sub-tabel di UI)
        $tables = [];
        $groupedBySub = $activeRows->groupBy('SubMapping');

        foreach ($groupedBySub as $subMappingName => $rows) {
            $totalPerBook = (int) $rows->sum('PerBook');
            $totalAjeRje = (int) $rows->sum('AJE_RJE');
            $totalAuditedBerjalan = (int) $rows->sum('AuditedBerjalan');
            $totalAuditSebelum = (int) $rows->sum('AuditSebelum');

            $tables[] = [
                'sub_mapping' => $subMappingName,
                'rows' => $rows->values()->all(),
                'totals' => [
                    'PerBook' => $totalPerBook,
                    'AJE_RJE' => $totalAjeRje,
                    'AuditedBerjalan' => $totalAuditedBerjalan,
                    'AuditSebelum' => $totalAuditSebelum,
                ],
            ];
        }

        // Daftar seluruh akun yang bisa dipilih di dropdown tombol '+' maupun saat mengganti akun
        $availableAccounts = $allCoasInCycle->map(function (COA $c) use ($allCorrections, $correctionsGrouped, $allCoasInCycle) {
            $corrections = $this->getCorrectionsForCoa($c, $allCorrections, $correctionsGrouped, $allCoasInCycle);
            $formatted = $this->formatAccountRow($c, $corrections);
            $formatted['SubMappingTop'] = $c->SubMappingTop ?: $c->MappingTop;

            return $formatted;
        })->values()->all();

        return [
            'top' => $topLetter,
            'mapping' => $mappingName,
            'TanggalPerBook' => $topSchedule->TanggalPerBook?->format('Y-m-d'),
            'TanggalAuditBerjalan' => $topSchedule->TanggalAuditBerjalan?->format('Y-m-d'),
            'TanggalAuditSebelum' => $topSchedule->TanggalAuditSebelum?->format('Y-m-d'),
            'IsFinal' => (bool) $topSchedule->IsFinal,
            'tables' => $tables,
            'available_accounts' => $availableAccounts,
        ];
    }

    /**
     * Menyimpan tanggal header dan susunan baris akun Top Schedule yang dikonfigurasi mahasiswa.
     */
    public function saveTopSchedule(JwbKasus $jwbKasus, array $payload): TopSchedule
    {
        $topLetter = strtoupper(trim($payload['top'] ?? 'A'));
        $mappingName = self::TOP_MAPPING[$topLetter] ?? self::TOP_MAPPING['A'];

        return DB::transaction(function () use ($jwbKasus, $topLetter, $mappingName, $payload) {
            $topSchedule = TopSchedule::updateOrCreate(
                [
                    'JwbKasusID' => $jwbKasus->JwbKasusID,
                    'Top' => $topLetter,
                ],
                [
                    'Mapping' => $mappingName,
                    'TanggalPerBook' => $payload['TanggalPerBook'] ?? null,
                    'TanggalAuditBerjalan' => $payload['TanggalAuditBerjalan'] ?? null,
                    'TanggalAuditSebelum' => $payload['TanggalAuditSebelum'] ?? null,
                    'IsFinal' => (bool) ($payload['IsFinal'] ?? false),
                ]
            );

            // Jika dikirim items kustom dari frontend, sinkronkan
            if (isset($payload['items']) && is_array($payload['items'])) {
                $topSchedule->items()->delete();

                $newItems = [];
                foreach ($payload['items'] as $index => $item) {
                    if (empty($item['COAID'])) {
                        continue;
                    }

                    $newItems[] = [
                        'TopScheduleID' => $topSchedule->TopScheduleID,
                        'COAID' => (int) $item['COAID'],
                        'SubMapping' => $item['SubMapping'] ?? $mappingName,
                        'Urutan' => (int) ($item['Urutan'] ?? $index),
                    ];
                }

                if (! empty($newItems)) {
                    $topSchedule->items()->createMany($newItems);
                }
            }

            return $topSchedule->fresh(['items.coa']);
        });
    }
}

