<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Perikatan;
use App\Models\Kasus;
use App\Models\DetilVerifikasi;
use App\Models\Identifikasi;
use App\Models\Pmpj;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JwbKasusController extends Controller
{
    /*
     * =====================================================
     * INDEX
     * =====================================================
     */
    public function index(Request $request): JsonResponse
    {
        $jawaban = JwbKasus::forUser($request->user())
            ->select([
                'JwbKasusID',
                'MahasiswasID',
                'KasusID',
                'JenisPerusahaan',
                'Periode',
                'WaktuMulai',
                'BatasWaktu',
                'Nilai',
            ])
            ->with([
                'kasus.kelas',
                'kasus.client',
                'mahasiswa.user',
            ])
            ->orderByDesc('JwbKasusID')
            ->get();

        return response()->json($jawaban);
    }


    /*
     * =====================================================
     * STORE
     * =====================================================
     *
     * Hanya mahasiswa. MahasiswasID selalu dari user yang login, bukan request body.
     */

    public function store(Request $request)
    {
        abort_if(! $request->user()->isMahasiswa(), 403);

        $validated = $request->validate([

            'KasusID' => [
                'required',
                'integer',
                'exists:kasus,KasusID',
            ],

            'JenisPerusahaan' => [
                'required',
                'in:Manufaktur,Dagang,Jasa',
            ],

            'Periode' => [
                'required',
                'date',
            ],

            'WaktuMulai' => [
                'required',
                'date',
            ],

            'BatasWaktu' => [
                'required',
                'date',
                'after_or_equal:WaktuMulai',
            ],
        ]);
        $mahasiswa = $request->user()->mahasiswa;

        /*
         * =====================================================
         * AMBIL KASUS
         * =====================================================
         */

        $kasus = Kasus::with('kelas')->findOrFail($validated['KasusID']);
        if (! $kasus->kelas) {
            return response()->json([
                'message' => 'Tugas tidak terhubung ke kelas manapun.',
            ], 404);
        }

        /*
         * Harus terdaftar di kelas pemilik kasus ini.
         */
        $enrolled = $kasus->kelas->mahasiswas()
            ->where('mahasiswas.id', $mahasiswa->id)
            ->exists();

        abort_unless($enrolled, 403, 'Anda tidak terdaftar di kelas ini.');

        /*
         * =====================================================
         * CEK DUPLIKAT
         * =====================================================
         */

        $existing = JwbKasus::where(
            'KasusID',
            $validated['KasusID']
        )
        ->where(
            'MahasiswasID',
            $mahasiswa->id
        )
        ->exists();

        if ($existing) {

            return response()->json([
                'message' => 'Mahasiswa tersebut sudah mengumpulkan jawaban untuk kasus ini.',
            ], 409);
        }

        /*
         * =====================================================
         * CREATE
         * =====================================================
         */
        
        $result = DB::transaction(function () use ($mahasiswa, $validated, $kasus) {
            $jawaban = JwbKasus::create([
                'MahasiswasID' => $mahasiswa->id,
                'KasusID' => $validated['KasusID'],
                'JenisPerusahaan' => $validated['JenisPerusahaan'],
                'Periode' => $validated['Periode'],
                'WaktuMulai' => $validated['WaktuMulai'],
                'BatasWaktu' => $validated['BatasWaktu'],
                'Nilai' => null,
            ]);

            $pmpj = Pmpj::create([
                'JwbKasusID' => $jawaban->JwbKasusID,
                'NamaPerusahaan' => $kasus->client?->NamaClient ?? $kasus->client?->NamaKantor,
                'AlamatPerusahaan' => $kasus->client?->AlamatKantor ?? $kasus->client?->AlamatClient,
                'TahunPeriode' => \Carbon\Carbon::parse($jawaban->Periode)->format('Y'),
            ]);

            $perikatan = Perikatan::create([
                'JwbKasusID' => $jawaban->JwbKasusID,
                'FileProposal' => null,
                'FileSPK' => null,
                'FileSuratTugas' => null,
                'FilePenugasan' => null,
                'FileIndependensi' => null,
            ]);

             $detilVerifikasi = DetilVerifikasi::create([
                'JwbKasusID' => $jawaban->JwbKasusID,
            ]);

            $identifikasi = Identifikasi::create([
                'JwbKasusID' => $jawaban->JwbKasusID,
            ]);

            return [
                'jawaban' => $jawaban,
                'perikatan' => $perikatan,
                'detilVerifikasi' => $detilVerifikasi,
                'identifikasi' => $identifikasi,
                'pmpj' => $pmpj,
            ];
        });

        $result['jawaban']->load([
            'kasus.kelas',
            'kasus.client',
            'mahasiswa.user',
            'pmpj',
        ]);

        /*
         * =====================================================
         * RESPONSE
         * =====================================================
         */

        return response()->json([
            'message' => 'Jawaban kasus berhasil dikumpulkan.',
            'data' => $result['jawaban'],
        ], 201);
    }

    /*
     * =====================================================
     * SHOW
     * =====================================================
     */

    public function show(Request $request, $id)
    {
        $jawaban = JwbKasus::with([
            'kasus.kelas',
            'mahasiswa.user',
        ])->findOrFail($id);

        abort_if(! $this->canAccess($request->user(), $jawaban), 403);

        return response()->json($jawaban);
    }

    /*
     * =====================================================
     * UPDATE
     * =====================================================
     *
     */

    public function update(Request $request, $id)
    {
        $jawaban = JwbKasus::with('kasus.kelas')->findOrFail($id);
        $isOwner = $request->user()->isMahasiswa()
            && $jawaban->MahasiswasID === $request->user()->mahasiswa->id;
        abort_if(! $this->canManage($request->user(), $jawaban) && ! $isOwner, 403);
        $validated = $request->validate([
            'KasusID' => [
                'sometimes',
                'integer',
                'exists:kasus,KasusID',
            ],
            'JenisPerusahaan' => [
                'sometimes',
                'in:Manufaktur,Dagang,Jasa',
            ],
            'Periode' => [
                'sometimes',
                'date',
            ],
            'WaktuMulai' => [
                'sometimes',
                'date',
            ],
            'BatasWaktu' => [
                'sometimes',
                'date',
                'after_or_equal:WaktuMulai',
            ],
            'Nilai' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $jawaban->update($validated);
        $jawaban->load([
            'kasus.kelas',
            'kasus.client',
            'mahasiswa.user',
        ]);

        return response()->json([
            'message' => 'Jawaban kasus berhasil diperbarui.',

            'data' => $jawaban,
        ]);
    }

    /*
     * =====================================================
     * DESTROY
     * =====================================================
     */

    public function destroy(Request $request, $id)
    {
        $jawaban = JwbKasus::with('kasus.kelas')->findOrFail($id);
        abort_if(! $this->canManage($request->user(), $jawaban), 403);
        $jawaban->delete();

        return response()->json([
            'message' => 'Jawaban kasus berhasil dihapus.',
        ]);
    }

    private function canAccess($user, JwbKasus $jawaban): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isMahasiswa()) {
            return $jawaban->MahasiswasID === $user->mahasiswa->id;
        }

        return $this->dosenPengampu($user, $jawaban);
    }

    private function canManage($user, JwbKasus $jawaban): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->dosenPengampu($user, $jawaban);
    }

    private function dosenPengampu($user, JwbKasus $jawaban): bool
    {
        if (! $user->isDosen() || ! $user->dosen) {
            return false;
        }
        $kelas = $jawaban->kasus ? $jawaban->kasus->kelas : null;

        return $kelas !== null && $kelas->dosen_id === $user->dosen->id;
    }
}