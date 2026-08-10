<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kelas;
use App\Models\Dosen;
use App\Models\Mahasiswa;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Dosen yang ada di database untuk dijadikan pengajar
        $dosen1 = Dosen::first();
        $dosen2 = Dosen::skip(1)->first(); 

        if (!$dosen1) {
            $this->command->info('Tidak ada data Dosen, harap buat Dosen terlebih dahulu.');
            return;
        }

        // 2. Buat Dummy Kelas 1
        $kelas1 = Kelas::create([
            'kode_kelas' => 'LA01',
            'hari' => 'Senin',
            'jam' => '09.20 - 11.00',
            'ruangan' => 'Anggrek - 401',
            'periode' => '2025 / 2026',
            'tipe_kelas' => 'Tugas',
            'dosen_id' => $dosen1->id,
        ]);

        // 3. Masukkan beberapa Mahasiswa ke Kelas 1 (Mengisi tabel perantara kelas_mahasiswa)
        // Mengambil 3 mahasiswa pertama
        $mahasiswas = Mahasiswa::take(3)->pluck('id')->toArray();
        if (!empty($mahasiswas)) {
            $kelas1->mahasiswas()->attach($mahasiswas);
        }

        // 4. Buat Dummy Kelas 2 (Jika ada Dosen ke-2)
        if ($dosen2) {
            $kelas2 = Kelas::create([
                'kode_kelas' => 'LC22',
                'hari' => 'Selasa',
                'jam' => '13.20 - 15.00',
                'ruangan' => 'Kijang - B301',
                'periode' => '2025 / 2026',
                'tipe_kelas' => 'UTS',
                'dosen_id' => $dosen2->id,
            ]);
            
            // Mengambil 2 mahasiswa selanjutnya
            $mahasiswas2 = Mahasiswa::skip(3)->take(2)->pluck('id')->toArray();
            if (!empty($mahasiswas2)) {
                $kelas2->mahasiswas()->attach($mahasiswas2);
            }
        }
    }
}