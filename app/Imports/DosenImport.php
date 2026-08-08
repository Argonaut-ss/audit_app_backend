<?php

namespace App\Imports;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DosenImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Dosen
    {
        return DB::transaction(function () use ($row) {
            $user = User::create([
                'name' => (string) $row['nama'],
                'email' => (string) $row['email'],
                'password' => Hash::make((string) ($row['password'] ?? $row['kode_dosen'])),
            ]);

            return new Dosen([
                'user_id' => $user->id,
                'kode_dosen' => (string) $row['kode_dosen'],
            ]);
        });
    }

    public function rules(): array
    {
        return [
            'kode_dosen' => 'required',
            'nama' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|min:6',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'kode_dosen.required' => 'Kode Dosen is required.',
            'nama.required' => 'Nama is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email already exists.',
        ];
    }
}