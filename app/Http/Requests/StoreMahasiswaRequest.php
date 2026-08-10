<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mahasiswaId = $this->route('mahasiswa')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($this->user_id ?? 'NULL')],
            'password' => [$mahasiswaId ? 'nullable' : 'required', 'string', 'min:6'],
            'nim' => ['required', 'string', 'max:50', 'unique:mahasiswas,nim,' . $mahasiswaId],
        ];
    }
}