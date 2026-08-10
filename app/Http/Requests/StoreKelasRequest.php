<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Mengizinkan request ini diproses
    }

    public function rules(): array
    {
        // Mengambil ID kelas jika sedang dalam mode 'update'
        $kelas = $this->route('kelas');
        $kelasId = $kelas ? $kelas->id : null;

        return [
            'kode_kelas' => ['required', 'string', 'max:50', 'unique:kelas,kode_kelas,' . $kelasId],
            'hari' => ['required', 'string'],
            'jam' => ['required', 'string'],
            'ruangan' => ['required', 'string'],
            'periode' => ['required', 'string'],
            'tipe_kelas' => ['required', 'in:UTS,UAS,Tugas,Sandbox'],
            'dosen_id' => ['required', 'exists:dosens,id'],
            
            // Validasi untuk array ID mahasiswa yang dipilih dari modal UI/UX kita
            'mahasiswa_ids' => ['nullable', 'array'], 
            'mahasiswa_ids.*' => ['exists:mahasiswas,id'], 
        ];
    }
}