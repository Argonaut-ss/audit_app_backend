<?php

namespace App\Http\Controllers;

use App\Models\AsetLain\JurnalKoreksiAsetLain;
use App\Models\AsetTetap\JurnalKoreksiAsetTetap;
use App\Models\BebanUsaha\JurnalKoreksiBebanUsaha;
use App\Models\Kas\JurnalKoreksiKas;
use App\Models\PendapatanUsaha\JurnalKoreksiPendapatanUsaha;
use App\Models\Persediaan\JurnalKoreksiPersediaan;
use App\Models\Piutang\JurnalKoreksi;
use App\Models\SetaraKas\JurnalKoreksiSetaraKas;
use App\Models\UtangUsaha\JurnalKoreksiUtangUsaha;
use App\Models\JwbKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekapJurnalKoreksiController extends Controller
{
    /**
     * Konfigurasi 9 modul pengujian substantif
     */
    protected array $modules = [
        [
            'nama_pengujian' => 'Kas',
            'model' => JurnalKoreksiKas::class,
            'parentRelation' => 'kas',
            'idKey' => 'JurnalKoreksiKasID',
        ],
        [
            'nama_pengujian' => 'Setara Kas',
            'model' => JurnalKoreksiSetaraKas::class,
            'parentRelation' => 'setaraKas',
            'idKey' => 'JurnalKoreksiSetaraKasID',
        ],
        [
            'nama_pengujian' => 'Piutang Usaha',
            'model' => JurnalKoreksi::class,
            'parentRelation' => 'piutang',
            'idKey' => 'JurnalKoreksiID',
        ],
        [
            'nama_pengujian' => 'Persediaan',
            'model' => JurnalKoreksiPersediaan::class,
            'parentRelation' => 'persediaan',
            'idKey' => 'JurnalKoreksiPersediaanID',
        ],
        [
            'nama_pengujian' => 'Beban Usaha',
            'model' => JurnalKoreksiBebanUsaha::class,
            'parentRelation' => 'bebanUsaha',
            'idKey' => 'JurnalKoreksiBebanUsahaID',
        ],
        [
            'nama_pengujian' => 'Aset Tetap',
            'model' => JurnalKoreksiAsetTetap::class,
            'parentRelation' => 'asetTetap',
            'idKey' => 'JurnalKoreksiAsetTetapID',
        ],
        [
            'nama_pengujian' => 'Utang Usaha',
            'model' => JurnalKoreksiUtangUsaha::class,
            'parentRelation' => 'utangUsaha',
            'idKey' => 'JurnalKoreksiUtangUsahaID',
        ],
        [
            'nama_pengujian' => 'Pendapatan Usaha',
            'model' => JurnalKoreksiPendapatanUsaha::class,
            'parentRelation' => 'pendapatanUsaha',
            'idKey' => 'JurnalKoreksiPendapatanUsahaID',
        ],
        [
            'nama_pengujian' => 'Aset Lain-Lain',
            'model' => JurnalKoreksiAsetLain::class,
            'parentRelation' => 'asetLain',
            'idKey' => 'JurnalKoreksiAsetLainID',
        ],
    ];

    public function show(Request $request, int $jwbKasusId): JsonResponse
    {
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $jwbKasusId)
            ->firstOrFail();

        $daftarPengujian = [];
        $uniqueAccounts = collect();
        $totalAllJournals = 0;
        $totalAllDebit = 0;
        $totalAllKredit = 0;

        foreach ($this->modules as $modConfig) {
            $modelClass = $modConfig['model'];
            $parentRel = $modConfig['parentRelation'];
            $idKey = $modConfig['idKey'];

            $journals = $modelClass::query()
                ->whereHas($parentRel, fn ($q) => $q->where('JwbKasusID', $jwbKasus->JwbKasusID))
                ->with(['pembayaranJurnalKoreksi.coa'])
                ->get();

            // Hanya tampilkan pengujian substantif yang memiliki minimal 1 jurnal
            if ($journals->isEmpty()) {
                continue;
            }

            $moduleJournals = [];
            foreach ($journals as $journal) {
                $pembayaranList = $journal->pembayaranJurnalKoreksi;

                $rincian = [];
                $journalDebit = 0;
                $journalKredit = 0;

                foreach ($pembayaranList as $item) {
                    $debit = (int) $item->Debet;
                    $kredit = (int) $item->Kredit;
                    $journalDebit += $debit;
                    $journalKredit += $kredit;

                    $coa = $item->coa;
                    $coaId = $coa ? $coa->COAID : $item->COAID;
                    $noAkun = $coa ? $coa->NoAkun : null;
                    $namaAkun = $coa ? $coa->NamaAkun : null;

                    $rincian[] = [
                        'COAID' => $coaId,
                        'NoAkun' => $noAkun,
                        'NamaAkun' => $namaAkun,
                        'Debit' => $debit,
                        'Kredit' => $kredit,
                    ];

                    if ($coaId && $noAkun && $namaAkun) {
                        $uniqueAccounts->push([
                            'COAID' => $coaId,
                            'NoAkun' => $noAkun,
                            'NamaAkun' => $namaAkun,
                        ]);
                    }
                }

                $moduleJournals[] = [
                    'id' => $journal->{$idKey},
                    'keterangan' => $journal->Keterangan ?? '',
                    'rincian' => $rincian,
                    'TotalDebit' => $journalDebit,
                    'TotalKredit' => $journalKredit,
                ];

                $totalAllJournals++;
                $totalAllDebit += $journalDebit;
                $totalAllKredit += $journalKredit;
            }

            $daftarPengujian[] = [
                'nama_pengujian' => $modConfig['nama_pengujian'],
                'jumlah_jurnal' => count($moduleJournals),
                'daftar_jurnal' => $moduleJournals,
            ];
        }

        // Akun-akun unik untuk filter dropdown
        $filterAkun = $uniqueAccounts
            ->unique('COAID')
            ->sortBy('NoAkun')
            ->values()
            ->all();

        return response()->json([
            'status_code' => 200,
            'message' => 'Data rekap jurnal koreksi berhasil diambil.',
            'data' => [
                'daftar_pengujian' => $daftarPengujian,
                'filter_akun' => $filterAkun,
                'ringkasan' => [
                    'total_pengujian' => count($daftarPengujian),
                    'total_jurnal' => $totalAllJournals,
                    'total_debit' => $totalAllDebit,
                    'total_kredit' => $totalAllKredit,
                ],
            ],
        ]);
    }
}
