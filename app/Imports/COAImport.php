<?php

namespace App\Imports;

use App\Models\COA;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class COAImport implements ToCollection, WithHeadingRow
{
    protected $user;

    protected int $jwbKasusID;

    public function __construct($user, int $jwbKasusID)
    {
        $this->user = $user;
        $this->jwbKasusID = $jwbKasusID;
    }

    public function headingRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows): void
    {
        $skipped = [];
        $imported = 0;
        $seenNoAkun = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 4;

            $noAkun = $this->nullableString(
                $row['no_akun'] ?? null
            );

            /*
             * Only import rows that have a No Akun.
             */
            if ($noAkun === null) {
                continue;
            }

            /*
             * Check duplicate NoAkun inside this Excel file.
             */
            if (isset($seenNoAkun[$noAkun])) {
                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $noAkun,
                    'reason' => 'No Akun is duplicated in the import file',
                ];

                continue;
            }

            /*
             * Check duplicate NoAkun already stored in DB.
             */
            if (
                COA::where('JwbKasusID', $this->jwbKasusID)
                    ->where('NoAkun', $noAkun)
                    ->exists()
            ) {
                $skipped[] = [
                    'row' => $rowNum,
                    'no_akun' => $noAkun,
                    'reason' => 'No Akun already exists for this JwbKasus',
                ];

                continue;
            }

            $saldo = $this->nullableString(
                $row['saldo_normal'] ?? null
            );

            if ($saldo !== null) {
                $saldo = ucfirst(strtolower($saldo));

                if (! in_array($saldo, ['Debit', 'Kredit'], true)) {
                    $skipped[] = [
                        'row' => $rowNum,
                        'no_akun' => $noAkun,
                        'reason' => 'Saldo Normal must be debit or kredit',
                    ];

                    continue;
                }
            }

            try {
                COA::create([
                    'JwbKasusID' => $this->jwbKasusID,

                    'NoAkun' => $noAkun,

                    'NamaAkun' => $this->nullableString(
                        $row['nama_akun'] ?? null
                    ),

                    'NamaLain' => $this->nullableString(
                        $row['nama_lain'] ?? null
                    ),

                    'MappingGroup' => $this->nullableString(
                        $row['mapping_group_akun'] ?? null
                    ),

                    'MapKelompok' => $this->nullableString(
                        $row['mapping_kelompok_akun'] ?? null
                    ),

                    'MappingTop' => $this->nullableString(
                        $row['mapping_top_schedule'] ?? null
                    ),

                    'SubMappingTop' => $this->nullableString(
                        $row['sub_mapping_top_schedule'] ?? null
                    ),

                    'Saldo' => $saldo,

                    'PerBook' => $this->nullableDecimal(
                        $row['per_book'] ?? null
                    ),

                    'AuditSebelum' => $this->nullableDecimal(
                        $row['audited_sebelum'] ?? null
                    ),
                ]);

                $seenNoAkun[$noAkun] = true;

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

    private function nullableString($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function nullableDecimal($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException(
                'Value must be numeric'
            );
        }

        return (float) $value;
    }
}