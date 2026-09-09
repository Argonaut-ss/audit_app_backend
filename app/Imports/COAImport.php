<?php

namespace App\Imports;

use App\Models\COA;
use App\Models\JwbKasus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class COAImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function collection(Collection $rows): void
    {
        $skipped = [];
        $imported = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $requiredFields = [
                'jwbkasusid',
                'noakun',
                'namaakun',
                'mappinggroup',
                'mapkelompok',
                'mappingtop',
                'submappingtop',
                'saldo',
                'perbook',
                'auditsebelum',
            ];

            $missing = false;

            foreach ($requiredFields as $field) {
                if (
                    ! isset($row[$field]) ||
                    trim((string) $row[$field]) === ''
                ) {
                    $missing = true;
                    break;
                }
            }

            if ($missing) {
                $skipped[] = [
                    'row' => $rowNum,
                    'reason' => 'Missing required fields',
                ];

                continue;
            }

            $jwbKasusID = (int) $row['jwbkasusid'];
            $noAkun = (int) $row['noakun'];
            $saldo = ucfirst(strtolower(trim((string) $row['saldo'])));

            if (! in_array($saldo, ['Debit', 'Kredit'], true)) {
                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $noAkun,
                    'reason' => 'Saldo must be Debit or Kredit',
                ];

                continue;
            }

            $jwbKasus = JwbKasus::forUser($this->user)
                ->where('JwbKasusID', $jwbKasusID)
                ->first();

            if (! $jwbKasus) {
                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $noAkun,
                    'reason' => 'JwbKasus not found or access denied',
                ];

                continue;
            }

            try {
                COA::create([
                    'JwbKasusID' => $jwbKasusID,
                    'NoAkun' => $noAkun,
                    'NamaAkun' => (string) $row['namaakun'],
                    'MappingGroup' => (string) $row['mappinggroup'],
                    'MapKelompok' => (string) $row['mapkelompok'],
                    'MappingTop' => (string) $row['mappingtop'],
                    'SubMappingTop' => (string) $row['submappingtop'],
                    'Saldo' => $saldo,
                    'PerBook' => (int) $row['perbook'],
                    'AuditSebelum' => (int) $row['auditsebelum'],
                ]);

                $imported++;
            } catch (\Throwable $e) {
                Log::warning(
                    'COA import failed for row ' . $rowNum,
                    [
                        'row' => $row->toArray(),
                        'error' => $e->getMessage(),
                    ]
                );

                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $noAkun,
                    'reason' => 'Database error: ' . $e->getMessage(),
                ];
            }
        }

        session()->put('coa_import_result', [
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $rows->count(),
        ]);
    }
}