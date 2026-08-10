<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MahasiswaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nim' => $this->nim,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'password' => '********', // Never return real password
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}