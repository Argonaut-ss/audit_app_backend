<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Kelas;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_kelas' => ['bail', 'required', 'string', 'max:50'], 
            'hari' => ['bail', 'required', 'string'],
            'jam' => ['bail', 'required', 'string'],
            'ruangan' => ['bail', 'required', 'string'],
            'periode' => ['bail', 'required', 'string'],
            'tipe_kelas' => ['bail', 'required', 'in:UTS,UAS,Tugas,Sandbox'],
            'dosen_id' => ['bail', 'required', 'exists:dosens,id'],
            
            'mahasiswa_ids' => ['nullable', 'array'], 
            'mahasiswa_ids.*' => ['bail', 'exists:mahasiswas,id'], 
        ];
    }

    /**
     * Validasi Custom Anti-Bentrok
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Gunakan errors()->any() agar tidak crash saat validasi awal gagal
            if ($validator->errors()->any()) return;

            // CARA AMAN AMBIL ID KELAS SAAT UPDATE:
            // Mengecek parameter 'kelas' maupun 'kela' baik berupa Objek Model atau ID String/Int
            $routeParam = $this->route('kelas');
            $kelasId = is_object($routeParam) ? $routeParam->id : $routeParam;

            // --- CEK 1: BENTROK RUANGAN ---
            $ruanganBentrok = Kelas::where('hari', $this->hari)
                ->where('jam', $this->jam)
                ->where('ruangan', $this->ruangan)
                ->when($kelasId, function ($query, $kelasId) {
                    return $query->where('id', '!=', $kelasId); // Mengabaikan ID kelas yang sedang diedit
                })
                ->exists();

            if ($ruanganBentrok) {
                $validator->errors()->add('ruangan', "Ruangan '{$this->ruangan}' sudah digunakan pada hari {$this->hari} jam {$this->jam}.");
                return; // Hentikan validasi custom jika ada bentrok ruangan
            }

            // --- CEK 2: BENTROK DOSEN ---
            $dosenBentrok = Kelas::where('hari', $this->hari)
                ->where('jam', $this->jam)
                ->where('dosen_id', $this->dosen_id)
                ->when($kelasId, function ($query, $kelasId) {
                    return $query->where('id', '!=', $kelasId); // Mengabaikan ID kelas yang sedang diedit
                })
                ->exists();

            if ($dosenBentrok) {
                $validator->errors()->add('dosen_id', "Dosen tersebut sudah memiliki jadwal mengajar pada hari {$this->hari} jam {$this->jam}.");
                return; // Hentikan validasi custom jika ada bentrok dosen
            }

            // --- CEK 3: DUPLIKASI KELAS YANG SAMA DI HARI & JAM SAMA ---
            $kelasSamaBentrok = Kelas::where('kode_kelas', $this->kode_kelas)
                ->where('hari', $this->hari)
                ->where('jam', $this->jam)
                ->when($kelasId, function ($query, $kelasId) {
                    return $query->where('id', '!=', $kelasId); // Mengabaikan ID kelas yang sedang diedit
                })
                ->exists();

            if ($kelasSamaBentrok) {
                $validator->errors()->add('kode_kelas', "Kelas '{$this->kode_kelas}' sudah memiliki jadwal pada hari {$this->hari} jam {$this->jam}.");
                return; // Hentikan validasi custom jika ada duplikasi kelas
            }

            // --- CEK 4: KONSISTENSI 1 KELAS = 1 DOSEN ---
            $dosenBerbeda = Kelas::where('kode_kelas', $this->kode_kelas)
                ->where('dosen_id', '!=', $this->dosen_id)
                ->when($kelasId, function ($query, $kelasId) {
                    return $query->where('id', '!=', $kelasId);
                })
                ->exists();

            if ($dosenBerbeda) {
                $validator->errors()->add('dosen_id', "Kelas '{$this->kode_kelas}' sudah terdaftar dan diajar oleh dosen lain. Satu kelas hanya boleh memiliki satu dosen.");
            }
        });
    }
}