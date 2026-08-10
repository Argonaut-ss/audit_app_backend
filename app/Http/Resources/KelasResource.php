<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_kelas' => $this->kode_kelas,
            'hari' => $this->hari,
            'jam' => $this->jam,
            'ruangan' => $this->ruangan,
            'periode' => $this->periode,
            'tipe_kelas' => $this->tipe_kelas,
            
            // Memanggil resource buatan temanmu agar datanya seragam
            'dosen' => new DosenResource($this->whenLoaded('dosen')),
            'mahasiswas' => MahasiswaResource::collection($this->whenLoaded('mahasiswas')),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}