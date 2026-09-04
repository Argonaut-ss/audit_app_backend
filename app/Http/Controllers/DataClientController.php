<?php

namespace App\Http\Controllers;

use App\Models\DataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $data = collect($clients->items())
            ->map(function ($client) {

                $client->LogoKantor =
                    $client->LogoKantor
                        ? 'data:image/webp;base64,' .
                        base64_encode($client->LogoKantor)
                        : null;

                $client->LogoPerusahaan =
                    $client->LogoPerusahaan
                        ? 'data:image/webp;base64,' .
                        base64_encode($client->LogoPerusahaan)
                        : null;

                return $client;
            })
            ->values();

        return response()->json([
            'data' => $data,
            
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
                'string',
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
                'string',
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

        if ($request->hasFile('LogoKantor')) {
            $file = $request->file('LogoKantor');

            $image = imagecreatefromstring(
                file_get_contents($file->getRealPath())
            );

            if ($image === false) {
                return response()->json([
                    'message' => 'Logo kantor tidak dapat diproses.',
                ], 422);
            }

            ob_start();

            imagewebp(
                $image,
                null,
                85
            );

            $webpData = ob_get_clean();

            imagedestroy($image);

            $validated['LogoKantor'] = $webpData;

            $validated['NamaLogoKantor'] =
                pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                ) . '.webp';
        }

        if ($request->hasFile('LogoPerusahaan')) {
            $file = $request->file('LogoPerusahaan');

            $image = imagecreatefromstring(
                file_get_contents($file->getRealPath())
            );

            if ($image === false) {
                return response()->json([
                    'message' => 'Logo perusahaan tidak dapat diproses.',
                ], 422);
            }

            ob_start();

            imagewebp(
                $image,
                null,
                85
            );

            $webpData = ob_get_clean();

            imagedestroy($image);

            $validated['LogoPerusahaan'] = $webpData;

            $validated['NamaLogoPerusahaan'] =
                pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                ) . '.webp';
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
     * LOGO KANTOR
     * =====================================================
     */

    public function logoKantor(Request $request, $id)
    {
        $client = DataClient::findOrFail($id);

        abort_if(! $this->canAccess($request->user(), $client), 403);

        if (!$client->LogoKantor) {
            return response()->json([
                'message' => 'Logo kantor tidak ditemukan.',
            ], 404);
        }

        return response(
            $client->LogoKantor,
            200,
            [
                'Content-Type' =>
                    'image/webp',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $client->NamaLogoKantor
                    ) .
                    '"',
            ]
        );
    }


    /*
     * =====================================================
     * LOGO PERUSAHAAN
     * =====================================================
     */

    public function logoPerusahaan(Request $request, $id)
    {
        $client = DataClient::findOrFail($id);

        abort_if(! $this->canAccess($request->user(), $client), 403);

        if (!$client->LogoPerusahaan) {
            return response()->json([
                'message' => 'Logo perusahaan tidak ditemukan.',
            ], 404);
        }

        return response(
            $client->LogoPerusahaan,
            200,
            [
                'Content-Type' =>
                    'image/webp',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $client->NamaLogoPerusahaan
                    ) .
                    '"',
            ]
        );
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
