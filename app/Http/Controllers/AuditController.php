<?php

namespace App\Http\Controllers;

/*
use App\Models\Audit;
use App\Models\Kasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if(! $user->isMahasiswa(), 403);

        $audits = Audit::where('user_id', $user->id)
            ->whereHas('kasus.kelas.mahasiswas', function ($query) use ($user) {
                $query->where('mahasiswa_id', $user->mahasiswa->id);
            })
            ->with([
                'user:id,name',
                'kasus.client:ClientID,NamaClient',
                'kasus.kelas:id,kode_kelas,tipe_kelas,KasusID',
            ])
            ->latest('AuditID')
            ->get()
            ->map(fn (Audit $audit) => $this->formatAudit($audit));

        return response()->json($audits);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if(! $user->isMahasiswa(), 403);

        $validated = $request->validate([
            'KasusID' => ['required', 'integer', 'exists:kasus,KasusID'],
            'jenis_perusahaan' => ['required', 'string', 'max:255'],
            'periode_audit' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date'],
            'batas_waktu' => ['required', 'date', 'after_or_equal:waktu_mulai'],
        ]);

        $kasus = Kasus::forUser($user)
            ->with(['client:ClientID,NamaClient', 'kelas:id,kode_kelas,tipe_kelas,KasusID'])
            ->findOrFail($validated['KasusID']);

        if (! $kasus->kelas) {
            return response()->json([
                'message' => 'Tugas belum terhubung ke kelas.',
            ], 422);
        }

        if (Audit::where('user_id', $user->id)
            ->where('KasusID', $kasus->KasusID)
            ->exists()) {
            return response()->json([
                'message' => 'Klien ini sudah memiliki data audit.',
            ], 409);
        }

        $audit = Audit::create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Data audit berhasil dibuat.',
            'data' => $this->formatAudit($audit->load([
                'user:id,name',
                'kasus.client:ClientID,NamaClient',
                'kasus.kelas:id,kode_kelas,tipe_kelas,KasusID',
            ])),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        abort_if(! $user->isMahasiswa(), 403);

        $audit = Audit::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'KasusID' => ['required', 'integer', 'exists:kasus,KasusID'],
            'jenis_perusahaan' => ['required', 'string', 'max:255'],
            'periode_audit' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date'],
            'batas_waktu' => ['required', 'date', 'after_or_equal:waktu_mulai'],
        ]);

        $kasus = Kasus::forUser($user)->findOrFail($validated['KasusID']);

        if (Audit::where('user_id', $user->id)
            ->where('KasusID', $kasus->KasusID)
            ->where('AuditID', '!=', $audit->AuditID)
            ->exists()) {
            return response()->json([
                'message' => 'Tugas ini sudah memiliki data audit.',
            ], 409);
        }

        $audit->update($validated);

        return response()->json([
            'message' => 'Data audit berhasil diperbarui.',
            'data' => $this->formatAudit($audit->load([
                'user:id,name',
                'kasus.client:ClientID,NamaClient',
                'kasus.kelas:id,kode_kelas,tipe_kelas,KasusID',
            ])),
        ]);
    }

    private function formatAudit(Audit $audit): array
    {
        return [
            'id' => $audit->AuditID,
            'KasusID' => $audit->KasusID,
            'tipe' => $audit->kasus?->kelas?->tipe_kelas,
            'nama' => $audit->user?->name,
            'klien' => $audit->kasus?->client?->NamaClient,
            'jenisPerusahaan' => $audit->jenis_perusahaan,
            'periodeAudit' => $audit->periode_audit?->format('Y-m-d'),
            'waktuMulai' => $audit->waktu_mulai?->format('Y-m-d'),
            'batasWaktu' => $audit->batas_waktu?->format('Y-m-d'),
        ];
    }
}
*/