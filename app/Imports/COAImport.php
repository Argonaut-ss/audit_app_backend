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

        foreach ($rows as $index => $row) {
            $rowNum = $index + 4;

            $saldo = $this->nullableString($row['saldo_normal'] ?? null);

            if ($saldo !== null) {
                $saldo = ucfirst(strtolower($saldo));

                if (! in_array($saldo, ['Debit', 'Kredit'], true)) {
                    $skipped[] = [
                        'row' => $rowNum,
                        'no_akun' => $this->nullableString(
                            $row['no_akun'] ?? null
                        ),
                        'reason' => 'Saldo Normal must be debit or kredit',
                    ];

                    continue;
                }
            }

            try {
                COA::create([
                    'JwbKasusID' => $this->jwbKasusID,

                    'NoAkun' => $this->nullableString(
                        $row['no_akun'] ?? null
                    ),

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
                    'no_akun' => $this->nullableString(
                        $row['no_akun'] ?? null
                    ),
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