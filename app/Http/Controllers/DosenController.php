<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDosenRequest;
use App\Http\Resources\DosenResource;
use App\Imports\DosenImport;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class DosenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $query = Dosen::with('user');

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('kode_dosen', 'like', "%{$search}%");
        }

        $dosens = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => DosenResource::collection($dosens),
            'meta' => [
                'current_page' => $dosens->currentPage(),
                'last_page' => $dosens->lastPage(),
                'per_page' => $dosens->perPage(),
                'total' => $dosens->total(),
            ],
        ]);
    }

    public function store(StoreDosenRequest $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $data = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $dosen = Dosen::create([
                'user_id' => $user->id,
                'kode_dosen' => $request->kode_dosen,
            ]);

            return $dosen->load('user');
        });

        return response()->json([
            'message' => 'Dosen created successfully',
            'data' => new DosenResource($data),
        ], 201);
    }

    public function show(Dosen $dosen, Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        return response()->json([
            'data' => new DosenResource($dosen->load('user')),
        ]);
    }

    public function update(StoreDosenRequest $request, Dosen $dosen): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $data = DB::transaction(function () use ($request, $dosen) {
            $dosen->user->update([
                'name' => $request->name,
                'email' => $request->email,
                ...( $request->filled('password') ? ['password' => Hash::make($request->password)] : [] ),
            ]);

            $dosen->update([
                'kode_dosen' => $request->kode_dosen,
            ]);

            return $dosen->load('user');
        });

        return response()->json([
            'message' => 'Dosen updated successfully',
            'data' => new DosenResource($data),
        ]);
    }

    public function destroy(Dosen $dosen, Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $dosen->user->delete();

        return response()->json([
            'message' => 'Dosen deleted successfully',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
        ]);

        Excel::import(new DosenImport, $request->file('file'));

        $result = session()->get('dosen_import_result', [
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