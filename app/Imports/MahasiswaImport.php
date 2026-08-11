<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MahasiswaImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $skipped = [];
        $imported = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            if (empty($row['nim']) || empty($row['nama']) || empty($row['email'])) {
                $skipped[] = [
                    'row' => $rowNum,
                    'nim' => $row['nim'] ?? null,
                    'reason' => 'Missing required fields (nim/nama/email)',
                ];
                continue;
            }

            if (User::where('email', (string) $row['email'])->exists()) {
                $skipped[] = [
                    'row' => $rowNum,
                    'nim' => (string) $row['nim'],
                    'reason' => 'Email already exists: ' . $row['email'],
                ];
                continue;
            }

            if (Mahasiswa::where('nim', (string) $row['nim'])->exists()) {
                $skipped[] = [
                    'row' => $rowNum,
                    'nim' => (string) $row['nim'],
                    'reason' => 'NIM already exists: ' . $row['nim'],
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($row) {
                    $user = User::create([
                        'name' => (string) $row['nama'],
                        'email' => (string) $row['email'],
                        'password' => Hash::make((string) ($row['password'] ?? $row['nim'])),
                    ]);

                    Mahasiswa::create([
                        'user_id' => $user->id,
                        'nim' => (string) $row['nim'],
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                Log::warning('Mahasiswa import failed for row ' . $rowNum, [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);

                $skipped[] = [
                    'row' => $rowNum,
                    'nim' => (string) $row['nim'],
                    'reason' => 'Database error: ' . $e->getMessage(),
                ];
            }
        }

        session()->put('mahasiswa_import_result', [
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $rows->count(),
        ]);
    }
}