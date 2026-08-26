<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\Kasus;
use App\Models\JwbKasus;
use App\Models\DataClient;
use App\Models\Perikatan;
use App\Models\DetilVerifikasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSeeder extends Seeder
{
    public function run(): void
    {
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

            $kelas2 = Kelas::updateOrCreate(
                ['id' => 2],
                [
                    'kode_kelas' => 'TEST02',
                    'hari' => 'Selasa',
                    'jam' => '13:00-15:00',
                    'ruangan' => 'TEST-ROOM-02',
                    'periode' => '2026/2027',
                    'tipe_kelas' => 'Tugas',
                    'dosen_id' => $dosen2->id,
                ]
            );

            $kelas2->mahasiswas()->syncWithoutDetaching([
                $mahasiswa2->id,
            ]);

            $client1 = DataClient::updateOrCreate(
                ['ClientID' => 1],
                [
                    'NamaClient' => 'PT Test Client',
                    'NamaKantor' => 'PT Test Client Office',
                    'JenisClient' => 'Perusahaan',
                    'NPWP' => '00.000.000.0-000.000',
                    'AlamatClient' => 'Jl. Test Client No. 1',
                    'AlamatKantor' => 'Jl. Test Office No. 1',
                    'HPClient' => '081234567890',
                    'HPKantor' => '02112345678',
                    'EmailClient' => 'client@test.local',
                    'EmailKantor' => 'office@test.local',
                    'URLClient' => 'https://client.test',
                    'URLKantor' => 'https://office.test',
                    'LogoKantor' => null,
                    'LogoPerusahaan' => null,
                ]
            );

            $kasus1 = Kasus::updateOrCreate(
                ['KasusID' => 1],
                [
                    'ClientID' => $client1->ClientID,
                    'NamaTugas' => 'API Test Case',
                    'NamaFile' => 'api-test-case.pdf',
                    'File' => self::fakePdf(),
                ]
            );

            $kelas1->update([
                'KasusID' => $kasus1->KasusID,
            ]);

            // JwbKasus::updateOrCreate(
            //     ['JwbKasusID' => 1],
            //     [
            //         'SubmisID' => 'TEST-SUB-001',
            //         'KasusID' => $kasus1->KasusID,
            //         'nim' => $mahasiswa1->nim,
            //         'TanggalUpload' => now(),
            //         'Nilai' => 85,
            //         'File' => self::fakePdf(),
            //     ]
            // );

            $jwbKasus1 = JwbKasus::updateOrCreate(
                ['JwbKasusID' => 1],
                [
                    'MahasiswasID' => $mahasiswa1->id,
                    'KasusID' => $kasus1->KasusID,
                    'JenisPerusahaan' => 'Manufaktur',
                    'Periode' => '2026-08-16',
                    'WaktuMulai' => '2026-08-20',
                    'BatasWaktu' => '2026-08-30',
                    'Nilai' => null,
                ]
            );

            Perikatan::updateOrCreate(
                ['JwbKasusID' => $jwbKasus1->JwbKasusID],
                [
                    'FileProposal' => null,
                    'FileSPK' => null,
                    'FileSuratTugas' => null,
                    'FilePenugasan' => null,
                    'FileIndependensi' => null,
                    'Pembuat' => null,
                ]
            );

            DetilVerifikasi::updateOrCreate(
                ['JwbKasusID' => $jwbKasus1->JwbKasusID],
                []
            );
        });
    }

    private static function fakePdf(): string
    {
        return "%PDF-1.4\n"
            . "1 0 obj\n"
            . "<< /Type /Catalog /Pages 2 0 R >>\n"
            . "endobj\n"
            . "2 0 obj\n"
            . "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n"
            . "endobj\n"
            . "3 0 obj\n"
            . "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\n"
            . "endobj\n"
            . "trailer\n"
            . "<< /Root 1 0 R >>\n"
            . "%%EOF";
    }
}