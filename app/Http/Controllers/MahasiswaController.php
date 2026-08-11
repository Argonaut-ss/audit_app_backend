<?php

namespace App\Http\Controllers;

use App\Imports\MahasiswaImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreMahasiswaRequest;
use App\Http\Resources\MahasiswaResource;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Mahasiswa::with('user');

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('nim', 'like', "%{$search}%");
        }

        $mahasiswas = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => MahasiswaResource::collection($mahasiswas),
            'meta' => [
                'current_page' => $mahasiswas->currentPage(),
                'last_page' => $mahasiswas->lastPage(),
                'per_page' => $mahasiswas->perPage(),
                'total' => $mahasiswas->total(),
            ],
        ]);
    }

    public function store(StoreMahasiswaRequest $request): JsonResponse
    {
        $data = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => $request->nim,
            ]);

            return $mahasiswa->load('user');
        });

        return response()->json([
            'message' => 'Mahasiswa created successfully',
            'data' => new MahasiswaResource($data),
        ], 201);
    }

    public function show(Mahasiswa $mahasiswa): JsonResponse
    {
        return response()->json([
            'data' => new MahasiswaResource($mahasiswa->load('user')),
        ]);
    }

    public function update(StoreMahasiswaRequest $request, Mahasiswa $mahasiswa): JsonResponse
    {
        $data = DB::transaction(function () use ($request, $mahasiswa) {
            $mahasiswa->user->update([
                'name' => $request->name,
                'email' => $request->email,
                ...( $request->filled('password') ? ['password' => Hash::make($request->password)] : [] ),
            ]);

            $mahasiswa->update([
                'nim' => $request->nim,
            ]);

            return $mahasiswa->load('user');
        });

        return response()->json([
            'message' => 'Mahasiswa updated successfully',
            'data' => new MahasiswaResource($data),
        ]);
    }

    public function destroy(Mahasiswa $mahasiswa): JsonResponse
    {
        $mahasiswa->user->delete(); // Cascade deletes mahasiswa via FK constraint

        return response()->json([
            'message' => 'Mahasiswa deleted successfully',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
        ]);

        Excel::import(new MahasiswaImport, $request->file('file'));

        $result = session()->get('mahasiswa_import_result', [
            'imported' => 0,
            'skipped' => [],
            'total' => 0,
        ]);

        return response()->json([
            'message' => 'Import completed',
            'imported' => $result['imported'],
            'skipped_count' => count($result['skipped']),
            'total_rows' => $result['total'],
            'skipped_rows' => $result['skipped'],
        ]);
    }
}