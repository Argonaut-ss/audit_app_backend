<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_kelas'      => $this->kode_kelas,
            'tipe_kelas'      => $this->tipe_kelas,
            'nama_dosen'      => $this->dosen?->user?->name,
            'kode_dosen'      => $this->dosen?->kode_dosen,
            'nama_perusahaan' => $this->kasus?->client?->NamaKantor,
        ];
    }
}