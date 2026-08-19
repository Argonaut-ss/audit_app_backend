<?php

namespace App\Http\Controllers;

use App\Http\Resources\KelasCardResource;
use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasCardController extends Controller
{
    public function index(Request $request)
    {
        $kelas = Kelas::forUser($request->user())
            ->with([
                'dosen.user',
                'kasus.client',
            ])
            ->get();

        return KelasCardResource::collection($kelas);
    }
}