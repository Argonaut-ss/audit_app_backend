<?php

namespace App\Http\Controllers;

use App\Http\Resources\KelasCardResource;
use App\Models\Kelas;
use Illuminate\Http\JsonResponse;

class KelasCardController extends Controller
{
    public function index(): JsonResponse
    {
        $kelas = Kelas::with([
            'dosen.user',
            'kasus.client',
        ])->get();

        return response()->json([
            'data' => KelasCardResource::collection($kelas),
        ]);
    }
}