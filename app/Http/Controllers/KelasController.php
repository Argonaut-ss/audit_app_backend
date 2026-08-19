<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKelasRequest;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Tarik data kelas sekalian dengan data dosen (dan user-nya untuk nama)
        $query = Kelas::forUser($request->user())->with(['dosen.user', 'mahasiswas.user', 'kasus.client']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_kelas', 'like', "%{$search}%")
                  ->orWhere('ruangan', 'like', "%{$search}%");
            });
        }

        $kelas = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => KelasResource::collection($kelas),
            'meta' => [
                'current_page' => $kelas->currentPage(),
                'last_page' => $kelas->lastPage(),
                'per_page' => $kelas->perPage(),
                'total' => $kelas->total(),
            ],
        ]);
    }

    public function store(StoreKelasRequest $request): JsonResponse

    {
        abort_if(! $request->user()->isAdmin(), 403);

        $data = DB::transaction(function () use ($request) {
            // 1. Simpan data Kelas
            $kelas = Kelas::create($request->validated());
            
            // 2. Simpan relasi Mahasiswa (jika ada mahasiswa yang di-checklist)
            if ($request->has('mahasiswa_ids')) {
                $kelas->mahasiswas()->sync($request->mahasiswa_ids);
            }

            return $kelas->load(['dosen.user', 'mahasiswas.user', 'kasus.client']);
        });

        return response()->json([
            'message' => 'Kelas created successfully',
            'data' => new KelasResource($data),
        ], 201);
    }

    public function show(Kelas $kelas, Request $request): JsonResponse
    {
        abort_if(! $this->canAccess($request->user(), $kelas), 403);

        return response()->json([
            'data' => new KelasResource($kelas->load(['dosen.user', 'mahasiswas.user', 'kasus.client'])),
        ]);
    }

    public function update(StoreKelasRequest $request, Kelas $kelas): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $data = DB::transaction(function () use ($request, $kelas) {
            // 1. Update data Kelas
            $kelas->update($request->validated());

            // 2. Update daftar Mahasiswa (sync akan otomatis menambah/menghapus relasi pivot)
            if ($request->has('mahasiswa_ids')) {
                $kelas->mahasiswas()->sync($request->mahasiswa_ids);
            }

            return $kelas->load(['dosen.user', 'mahasiswas.user', 'kasus.client']);
        });

        return response()->json([
            'message' => 'Kelas updated successfully',
            'data' => new KelasResource($data),
        ]);
    }

    public function destroy(Kelas $kelas, Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);
        
        $kelas->delete();

        return response()->json([
            'message' => 'Kelas deleted successfully',
        ]);
    }
    
        private function canAccess($user, Kelas $kelas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDosen()) {
            return $kelas->dosen_id === $user->dosen->id;
        }

        if ($user->isMahasiswa()) {
            return $kelas->mahasiswas()->where('mahasiswa_id', $user->mahasiswa->id)->exists();
        }

        return false;
    }
}