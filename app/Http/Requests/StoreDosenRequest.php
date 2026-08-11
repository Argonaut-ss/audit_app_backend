<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDosenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dosen = $this->route('dosen');
        $userId = $dosen?->user_id;
        $dosenId = $dosen?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => [$dosen ? 'nullable' : 'required', 'string', 'min:6'],
            'kode_dosen' => ['required', 'string', 'max:50', 'unique:dosens,kode_dosen,' . $dosenId],
        ];
    }
}