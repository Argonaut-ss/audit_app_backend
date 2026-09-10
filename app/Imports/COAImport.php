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

            // JwbKasusID is the only required field because it is
            // needed to determine which case the COA belongs to
            // and whether the user has access to it.
            if (
                ! isset($row['jwbkasusid']) ||
                trim((string) $row['jwbkasusid']) === ''
            ) {
                $skipped[] = [
                    'row' => $rowNum,
                    'reason' => 'Missing required field: JwbKasusID',
                ];

                continue;
            }

            $jwbKasusID = (int) $row['jwbkasusid'];

            // Match the controller's Saldo validation:
            // nullable, but if provided it must be Debit or Kredit.
            $saldo = null;

            if (
                isset($row['saldo']) &&
                trim((string) $row['saldo']) !== ''
            ) {
                $saldo = ucfirst(strtolower(trim((string) $row['saldo'])));

                if (! in_array($saldo, ['Debit', 'Kredit'], true)) {
                    $skipped[] = [
                        'row' => $rowNum,
                        'no_akun' => $this->nullableInt($row['noakun'] ?? null),
                        'reason' => 'Saldo must be Debit or Kredit',
                    ];

                    continue;
                }
            }

            $jwbKasus = JwbKasus::forUser($this->user)
                ->where('JwbKasusID', $jwbKasusID)
                ->first();

            if (! $jwbKasus) {
                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $this->nullableInt($row['noakun'] ?? null),
                    'reason' => 'JwbKasus not found or access denied',
                ];

                continue;
            }

            try {
                COA::create([
                    'JwbKasusID' => $jwbKasusID,
                    'NoAkun' => $this->nullableInt($row['noakun'] ?? null),
                    'NamaAkun' => $this->nullableString($row['namaakun'] ?? null),
                    'MappingGroup' => $this->nullableString($row['mappinggroup'] ?? null),
                    'MapKelompok' => $this->nullableString($row['mapkelompok'] ?? null),
                    'MappingTop' => $this->nullableString($row['mappingtop'] ?? null),
                    'SubMappingTop' => $this->nullableString($row['submappingtop'] ?? null),
                    'Saldo' => $saldo,
                    'PerBook' => $this->nullableInt($row['perbook'] ?? null),
                    'AuditSebelum' => $this->nullableInt($row['auditsebelum'] ?? null),
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
                    'no_akun' => $this->nullableInt($row['noakun'] ?? null),
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

    private function nullableInt($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }
}