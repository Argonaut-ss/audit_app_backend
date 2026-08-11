<?php

namespace App\Imports;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DosenImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $skipped = [];
        $imported = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            if (empty($row['kode_dosen']) || empty($row['nama']) || empty($row['email'])) {
                $skipped[] = [
                    'row' => $rowNum,
                    'kode_dosen' => $row['kode_dosen'] ?? null,
                    'reason' => 'Missing required fields (kode_dosen/nama/email)',
                ];
                continue;
            }

            if (User::where('email', (string) $row['email'])->exists()) {
                $skipped[] = [
                    'row' => $rowNum,
                    'kode_dosen' => (string) $row['kode_dosen'],
                    'reason' => 'Email already exists: ' . $row['email'],
                ];
                continue;
            }

            if (Dosen::where('kode_dosen', (string) $row['kode_dosen'])->exists()) {
                $skipped[] = [
                    'row' => $rowNum,
                    'kode_dosen' => (string) $row['kode_dosen'],
                    'reason' => 'Kode Dosen already exists: ' . $row['kode_dosen'],
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($row) {
                    $user = User::create([
                        'name' => (string) $row['nama'],
                        'email' => (string) $row['email'],
                        'password' => Hash::make((string) ($row['password'] ?? $row['kode_dosen'])),
                    ]);

                    Dosen::create([
                        'user_id' => $user->id,
                        'kode_dosen' => (string) $row['kode_dosen'],
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                Log::warning('Dosen import failed for row ' . $rowNum, [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);

                $skipped[] = [
                    'row' => $rowNum,
                    'kode_dosen' => (string) $row['kode_dosen'],
                    'reason' => 'Database error: ' . $e->getMessage(),
                ];
            }
        }

        session()->put('dosen_import_result', [
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $rows->count(),
        ]);
    }
}