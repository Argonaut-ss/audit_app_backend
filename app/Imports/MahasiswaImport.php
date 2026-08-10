<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MahasiswaImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Mahasiswa
    {
        return DB::transaction(function () use ($row) {
            $user = User::create([
                'name' => (string) $row['nama'],
                'email' => (string) $row['email'],
                'password' => Hash::make((string) ($row['password'] ?? $row['nim'])),
            ]);

            return new Mahasiswa([
                'user_id' => $user->id,
                'nim' => (string) $row['nim'],
            ]);
        });
    }

    public function rules(): array
    {
        return [
            'nim' => 'required',
            'nama' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|min:6',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nim.required' => 'NIM is required.',
            'nama.required' => 'Nama is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email already exists.',
        ];
    }
}