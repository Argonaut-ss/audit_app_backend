<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // User Admin
        DB::transaction(function () {
            $admin = User::updateOrCreate(
                ['id' => 1],
                [
                    'name' => 'Admin Test',
                    'email' => 'admin@binus.ac.id',
                    'password' => 'Admin123',
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]
            );

            // User Mhs
            $mahasiswaUser1 = User::updateOrCreate(
                ['email' => 'adrian.ananta@binus.ac.id'],
                [
                    'name' => 'Adrian Ananta',
                    'password' => 'Adrian123',
                    'role' => 'mahasiswa',
                    'email_verified_at' => now(),
                ]
            );

            $mahasiswa1 = Mahasiswa::updateOrCreate(
                ['id' => 1],
                [
                    'user_id' => $mahasiswaUser1->id,
                    'nim' => '2702357402',
                ]
            );

            $mahasiswaUser2 = User::updateOrCreate(
                ['email' => 'budi.santoso@binus.ac.id'],
                [
                    'name' => 'Budi Santoso',
                    'password' => 'Budi123',
                    'role' => 'mahasiswa',
                    'email_verified_at' => now(),
                ]
            );

            $mahasiswa2 = Mahasiswa::updateOrCreate(
                ['id' => 2],
                [
                    'user_id' => $mahasiswaUser2->id,
                    'nim' => '2702211103',
                ]
            );

            $dosenUser1 = User::updateOrCreate(
                ['email' => 'wono.jair@binus.edu'],
                [
                    'name' => 'Wono Jair',
                    'password' => 'Wono123',
                    'role' => 'dosen',
                    'email_verified_at' => now(),
                ]
            );

            // User Dosen
            $dosen1 = Dosen::updateOrCreate(
                ['id' => 1],
                [
                    'user_id' => $dosenUser1->id,
                    'kode_dosen' => 'D1232',
                ]
            );

            $dosenUser2 = User::updateOrCreate(
                ['email' => 'siti.aninsyah@binus.edu'],
                [
                    'name' => 'Siti Aninsyah',
                    'password' => 'Siti123',
                    'role' => 'dosen',
                    'email_verified_at' => now(),
                ]
            );

            $dosen2 = Dosen::updateOrCreate(
                ['id' => 2],
                [
                    'user_id' => $dosenUser2->id,
                    'kode_dosen' => 'D2456',
                ]
            );

            // Kelas
            $kelas1 = Kelas::updateOrCreate(
                ['id' => 1],
                [
                    'kode_kelas' => 'TEST01',
                    'hari' => 'Senin',
                    'jam' => '10:00-12:00',
                    'ruangan' => 'TEST-ROOM-01',
                    'periode' => '2026/2027',
                    'tipe_kelas' => 'Tugas',
                    'dosen_id' => $dosen1->id,
                ]
            );

            $kelas1->mahasiswas()->syncWithoutDetaching([
                $mahasiswa1->id,
            ]);
        });
    }
}