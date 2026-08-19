<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\Kasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JwbKasusController extends Controller
{
    /*
     * =====================================================
     * INDEX
     * =====================================================
     *
     * Admin: semua jawaban.
     * Dosen: jawaban untuk kasus di kelas yang diampu.
     * Mahasiswa: jawaban miliknya sendiri saja.
     *
     * File binary tidak ikut dikirim.
     */

    public function index(Request $request): JsonResponse
    {
        $jawaban = JwbKasus::forUser($request->user())
            ->select([
                'JwbKasusID',
                'SubmisID',
                'KasusID',
                'nim',
                'TanggalUpload',
                'Nilai',
            ])
            ->with([
                'kasus.kelas',
                'mahasiswa.user',
            ])
            ->orderByDesc('TanggalUpload')
            ->get();

        return response()->json($jawaban);
    }


    /*
     * =====================================================
     * STORE
     * =====================================================
     *
     * Hanya mahasiswa. NIM selalu dari user yang login, bukan request body.
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

            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx',
            ],
        ]);


        $mahasiswa = $request->user()->mahasiswa;


        /*
         * =====================================================
         * AMBIL KASUS
         * =====================================================
         */

        $kasus = Kasus::with('kelas')->findOrFail(
            $validated['KasusID']
        );

        if (! $kasus->kelas) {
            return response()->json([
                'message' => 'Tugas tidak terhubung ke kelas manapun.',
            ], 404);
        }


        /*
         * Harus terdaftar di kelas pemilik kasus ini.
         */
        $enrolled = $kasus->kelas->mahasiswas()
            ->where('mahasiswa_id', $mahasiswa->id)
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
            'nim',
            $mahasiswa->nim
        )
        ->exists();


        if ($existing) {

            return response()->json([
                'message' =>
                    'Mahasiswa tersebut sudah mengumpulkan jawaban untuk kasus ini.',
            ], 409);
        }


        /*
         * =====================================================
         * FILE
         * =====================================================
         */

        $uploadedFile = $request->file('file');


        $fileContent = file_get_contents(
            $uploadedFile->getRealPath()
        );


        /*
         * =====================================================
         * SUBMISSION ID
         * =====================================================
         */

        $submisID = 'SUB-' . strtoupper(
            Str::random(12)
        );


        /*
         * =====================================================
         * CREATE
         * =====================================================
         */

        $jawaban = JwbKasus::create([

            'SubmisID' => $submisID,

            'KasusID' => $validated['KasusID'],

            'nim' => $mahasiswa->nim,

            'TanggalUpload' => now(),

            'Nilai' => null,

            'File' => $fileContent,
        ]);


        /*
         * =====================================================
         * RESPONSE
         * =====================================================
         */

        return response()->json([
            'message' =>
                'Jawaban kasus berhasil dikumpulkan.',

            'data' => [
                'JwbKasusID' =>
                    $jawaban->JwbKasusID,

                'SubmisID' =>
                    $jawaban->SubmisID,

                'KasusID' =>
                    $jawaban->KasusID,

                'NIM' =>
                    $jawaban->nim,

                'TanggalUpload' =>
                    $jawaban->TanggalUpload,
            ],
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
     * FILE
     * =====================================================
     *
     * Download file jawaban. Previously PUBLIC.
     */

    public function file(Request $request, $id)
    {
        $jawaban = JwbKasus::with('kasus.kelas')->findOrFail($id);

        abort_if(! $this->canAccess($request->user(), $jawaban), 403);

        if (! $jawaban->File) {

            return response()->json([
                'message' =>
                    'File jawaban tidak ditemukan.',
            ], 404);
        }

        return response(
            $jawaban->File,
            200,
            [
                'Content-Type' =>
                    'application/octet-stream',

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $jawaban->SubmisID
                    ) .
                    '"',
            ]
        );
    }


    /*
     * =====================================================
     * UPDATE
     * =====================================================
     *
     * Memberi nilai — Admin atau Dosen pengampu kelas saja.
     */

    public function update(Request $request, $id)
    {
        $jawaban = JwbKasus::with('kasus.kelas')->findOrFail($id);

        abort_if(! $this->canManage($request->user(), $jawaban), 403);

        $validated = $request->validate([

            'Nilai' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);


        $jawaban->update([
            'Nilai' =>
                $validated['Nilai'] ?? null,
        ]);


        return response()->json([
            'message' =>
                'Jawaban kasus berhasil diperbarui.',

            'data' => [
                'JwbKasusID' =>
                    $jawaban->JwbKasusID,

                'SubmisID' =>
                    $jawaban->SubmisID,

                'KasusID' =>
                    $jawaban->KasusID,

                'NIM' =>
                    $jawaban->nim,

                'TanggalUpload' =>
                    $jawaban->TanggalUpload,

                'Nilai' =>
                    $jawaban->Nilai,
            ],
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
            'message' =>
                'Jawaban kasus berhasil dihapus.',
        ]);
    }

    private function canAccess($user, JwbKasus $jawaban): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isMahasiswa()) {
            return $jawaban->nim === $user->mahasiswa->nim;
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