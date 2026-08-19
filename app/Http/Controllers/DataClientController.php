<?php

namespace App\Http\Controllers;

use App\Models\DataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DataClientController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $query = DataClient::forUser($request->user());

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('NamaClient', 'like', "%{$search}%")
                  ->orWhere('NamaKantor', 'like', "%{$search}%")
                  ->orWhere('NPWP', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('ClientID', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page'    => $clients->lastPage(),
                'per_page'     => $clients->perPage(),
                'total'        => $clients->total(),
            ],
        ]);
    }


    /*
     * =====================================================
     * STORE
     * =====================================================
     */

    public function store(Request $request)
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'NamaClient' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $client = DataClient::create([
            'NamaClient' => trim($validated['NamaClient']),
        ]);

        return response()->json([
            'message' => 'Data client berhasil dibuat.',
            'data'    => $client,
        ], 201);
    }


    /*
     * =====================================================
     * SHOW
     * =====================================================
     */

    public function show(Request $request, $id): JsonResponse
    {
        $client = DataClient::findOrFail($id);

        abort_if(! $this->canAccess($request->user(), $client), 403);

        return response()->json([
            'data' => $client,
        ]);
    }


    /*
     * =====================================================
     * UPDATE
     * =====================================================
     */

    public function update(Request $request, $id)
    {
        $user   = $request->user();
        $client = DataClient::findOrFail($id);

        $isAdmin          = $user->isAdmin();
        $isMahasiswaSahih = $user->isMahasiswa() && $this->canAccess($user, $client);

        abort_if(! $isAdmin && ! $isMahasiswaSahih, 403);

        $validated = $request->validate([

            'NamaClient' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'JenisClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NPWP' => [
                'nullable',
                'string',
                'max:255',
            ],

            'AlamatClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPClient' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailClient' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLClient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'NamaKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'AlamatKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'HPKantor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'EmailKantor' => [
                'nullable',
                'email',
                'max:255',
            ],

            'URLKantor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'LogoKantor' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'LogoPerusahaan' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        /*
         * NamaClient adalah wewenang Admin saja.
         */
        if (! $isAdmin) {
            unset($validated['NamaClient']);
        }

        foreach (['LogoKantor' => 'kantor', 'LogoPerusahaan' => 'perusahaan'] as $field => $prefix) {
            if ($request->hasFile($field)) {
                $validated[$field] = $this->replaceLogo($client, $field, $request->file($field), $prefix);
            }
        }

        /*
         * =================================================
         * UPDATE DATABASE
         * =================================================
         */

        $client->update($validated);

        return response()->json([
            'message' => 'Data client berhasil diperbarui.',
            'data'    => $client,
        ]);
    }


    /*
     * =====================================================
     * DESTROY
     * =====================================================
     */

    public function destroy(Request $request, $id)
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $client = DataClient::findOrFail($id);

        /*
         * Jangan hapus client jika masih
         * digunakan oleh kasus/tugas.
         */

        if ($client->kasus()->exists()) {
            return response()->json([
                'message' => 'Client tidak dapat dihapus karena masih digunakan oleh tugas/kasus.',
            ], 409);
        }

        foreach (['LogoKantor', 'LogoPerusahaan'] as $field) {
            if ($client->{$field}) {
                $path = public_path($client->{$field});
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }

        $client->delete();

        return response()->json([
            'message' => 'Data client berhasil dihapus.',
        ]);
    }


    /*
     * =====================================================
     * FILE HELPERS & AUTHORIZATION
     * =====================================================
     */

    private function replaceLogo(DataClient $client, string $field, $file, string $prefix): string
    {
        if ($client->{$field}) {
            $old = public_path($client->{$field});
            if (File::exists($old)) {
                File::delete($old);
            }
        }

        $logoPath = public_path('DataClient/Logo');

        if (! File::exists($logoPath)) {
            File::makeDirectory($logoPath, 0755, true);
        }

        $fileName = $prefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($logoPath, $fileName);

        return 'DataClient/Logo/' . $fileName;
    }

    private function canAccess($user, DataClient $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDosen()) {
            return $client->kasus()
                ->whereHas('kelas', function ($q) use ($user) {
                    $q->where('dosen_id', $user->dosen->id);
                })
                ->exists();
        }

        if ($user->isMahasiswa()) {
            return $client->kasus()
                ->whereHas('kelas.mahasiswas', function ($q) use ($user) {
                    $q->where('mahasiswa_id', $user->mahasiswa->id);
                })
                ->exists();
        }

        return false;
    }
}