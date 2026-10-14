<?php

namespace App\Http\Controllers\AsetLainController;

use App\Http\Controllers\Controller;
use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\DokumentasiAsetLain;
use App\Models\JwbKasus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DokumentasiAsetLainController extends Controller
{
    protected function resolveAuthorizedAsetLain(Request $request, int $asetLainId): AsetLain
    {
        $asetLain = AsetLain::findOrFail($asetLainId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $asetLain->JwbKasusID)
            ->firstOrFail();

        return $asetLain;
    }

    private function dokumentasiCheck(int $asetLainId): void
    {
        $asetLain = AsetLain::find($asetLainId);

        if (! $asetLain) {
            return;
        }

        $asetLain->updateQuietly([
            'DokumentasiCheck' => $asetLain->dokumentasi()->exists(),
        ]);
    }

    public static function calculateDurationMonths(?string $startDate, ?string $endDate): int
    {
        if (! $startDate || ! $endDate || $endDate < $startDate) {
            return 0;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);

        $anniversary = $start->copy()->addMonthsNoOverflow($months);
        if ($anniversary->gt($end)) {
            $months -= 1;
        }

        $fullMonthsDate = $start->copy()->addMonthsNoOverflow($months);
        $remainingDays = (int) $fullMonthsDate->diffInDays($end, false);

        if ($remainingDays >= 16) {
            $months += 1;
        }

        return max(0, $months);
    }

    protected function calculate(array $data): array
    {
        $nominal = (float) ($data['Nominal'] ?? 0);
        $masaPemakaian = (int) ($data['MasaPemakaian'] ?? 0);
        $perusahaan = (float) ($data['Perusahaan'] ?? 0);

        $jangkaWaktu = self::calculateDurationMonths(
            $data['TanggalMulai'] ?? null,
            $data['TanggalAkhir'] ?? null
        );

        $amortisasiBulan = $jangkaWaktu > 0
            ? round($nominal / $jangkaWaktu, 2)
            : 0.0;

        $amortisasiAkhirTahun = $nominal > 0 && $amortisasiBulan > 0 && $masaPemakaian > 0
            ? min(round($masaPemakaian * $amortisasiBulan, 2), $nominal)
            : 0.0;

        $audit = $nominal > 0
            ? round($nominal - $amortisasiAkhirTahun, 2)
            : 0.0;

        $selisih = round($audit - $perusahaan, 2);

        return [
            'NomorPerjanjian' => trim($data['NomorPerjanjian'] ?? ''),
            'Objek' => trim($data['Objek'] ?? ''),
            'TanggalPerjanjian' => $data['TanggalPerjanjian'] ?? null,
            'TanggalMulai' => $data['TanggalMulai'] ?? null,
            'TanggalAkhir' => $data['TanggalAkhir'] ?? null,
            'Nominal' => $nominal,
            'JangkaWaktu' => $jangkaWaktu,
            'MasaPemakaian' => $masaPemakaian,
            'AmortisasiBulan' => $amortisasiBulan,
            'AmortisasiAkhirTahun' => $amortisasiAkhirTahun,
            'Audit' => $audit,
            'Perusahaan' => $perusahaan,
            'Selisih' => $selisih,
        ];
    }

    protected function serializeItem(DokumentasiAsetLain $item): array
    {
        return [
            'DokumentasiID' => $item->DokumentasiID,
            'AsetLainID' => $item->AsetLainID,
            'NomorPerjanjian' => $item->NomorPerjanjian,
            'Objek' => $item->Objek,
            'TanggalPerjanjian' => $item->TanggalPerjanjian?->format('Y-m-d'),
            'TanggalMulai' => $item->TanggalMulai?->format('Y-m-d'),
            'TanggalAkhir' => $item->TanggalAkhir?->format('Y-m-d'),
            'Nominal' => (float) $item->Nominal,
            'JangkaWaktu' => (int) $item->JangkaWaktu,
            'MasaPemakaian' => (int) $item->MasaPemakaian,
            'AmortisasiBulan' => (float) $item->AmortisasiBulan,
            'AmortisasiAkhirTahun' => (float) $item->AmortisasiAkhirTahun,
            'Audit' => (float) $item->Audit,
            'Perusahaan' => (float) $item->Perusahaan,
            'Selisih' => (float) $item->Selisih,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    protected function rowRules(string $prefix = ''): array
    {
        return [
            "{$prefix}NomorPerjanjian" => ['required', 'string', 'max:255'],
            "{$prefix}Objek" => ['required', 'string', 'max:255'],
            "{$prefix}TanggalPerjanjian" => ['required', 'date'],
            "{$prefix}TanggalMulai" => ['required', 'date'],
            "{$prefix}TanggalAkhir" => ['required', 'date', "after_or_equal:{$prefix}TanggalMulai"],
            "{$prefix}Nominal" => ['required', 'numeric', 'min:0'],
            "{$prefix}MasaPemakaian" => ['required', 'integer', 'min:0'],
            "{$prefix}Perusahaan" => ['nullable', 'numeric'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
        ]);

        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $validated['AsetLainID']);

        $items = $asetLain->dokumentasi()
            ->orderBy('DokumentasiID')
            ->get()
            ->map(fn (DokumentasiAsetLain $item) => $this->serializeItem($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Mendukung mode bulk save (array rows) atau single item create
        if ($request->has('rows')) {
            return $this->bulkSave($request);
        }

        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
            ...$this->rowRules(),
        ]);

        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $validated['AsetLainID']);

        $item = DokumentasiAsetLain::create(array_merge(
            ['AsetLainID' => $asetLain->AsetLainID],
            $this->calculate($validated)
        ));

        $this->dokumentasiCheck((int) $asetLain->AsetLainID);

        return response()->json([
            'success' => true,
            'message' => 'Data dokumentasi aset lain-lain berhasil disimpan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function bulkSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'AsetLainID' => ['required', 'integer', 'exists:aset_lain,AsetLainID'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.DokumentasiID' => ['nullable', 'integer', 'exists:dokumentasi_aset_lain,DokumentasiID'],
            ...$this->rowRules('rows.*.'),
        ]);

        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $validated['AsetLainID']);

        $docIds = collect($validated['rows'])
            ->pluck('DokumentasiID')
            ->filter(fn ($id) => ! is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existing = DokumentasiAsetLain::whereIn('DokumentasiID', $docIds)
            ->get()
            ->keyBy('DokumentasiID');

        foreach ($docIds as $id) {
            $row = $existing->get($id);
            if (! $row) {
                throw ValidationException::withMessages([
                    'rows' => "Dokumentasi ID {$id} tidak ditemukan.",
                ]);
            }

            if ((int) $row->AsetLainID !== (int) $asetLain->AsetLainID) {
                throw ValidationException::withMessages([
                    'rows' => "Dokumentasi ID {$id} bukan milik Aset Lain ini.",
                ]);
            }
        }

        $saved = DB::transaction(function () use ($validated, $asetLain, $existing) {
            $result = [];

            foreach ($validated['rows'] as $row) {
                $payload = $this->calculate($row);

                if (! empty($row['DokumentasiID'])) {
                    $item = $existing->get((int) $row['DokumentasiID']);
                    $item->update($payload);
                } else {
                    $item = $asetLain->dokumentasi()->create(array_merge(
                        ['AsetLainID' => $asetLain->AsetLainID],
                        $payload
                    ));
                }

                $result[] = $item->fresh();
            }

            $this->dokumentasiCheck((int) $asetLain->AsetLainID);

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data dokumentasi aset lain-lain berhasil disimpan.',
            'data' => collect($saved)
                ->map(fn (DokumentasiAsetLain $item) => $this->serializeItem($item))
                ->values(),
        ]);
    }

    public function show(Request $request, DokumentasiAsetLain $dokumentasiAsetLain): JsonResponse
    {
        $this->resolveAuthorizedAsetLain($request, (int) $dokumentasiAsetLain->AsetLainID);

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($dokumentasiAsetLain),
        ]);
    }

    public function update(Request $request, DokumentasiAsetLain $dokumentasiAsetLain): JsonResponse
    {
        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $dokumentasiAsetLain->AsetLainID);

        $validated = $request->validate($this->rowRules());

        $dokumentasiAsetLain->update($this->calculate($validated));

        $this->dokumentasiCheck((int) $asetLain->AsetLainID);

        return response()->json([
            'success' => true,
            'message' => 'Data dokumentasi aset lain-lain berhasil diperbarui.',
            'data' => $this->serializeItem($dokumentasiAsetLain->fresh()),
        ]);
    }

    public function destroy(Request $request, DokumentasiAsetLain $dokumentasiAsetLain): JsonResponse
    {
        $asetLain = $this->resolveAuthorizedAsetLain($request, (int) $dokumentasiAsetLain->AsetLainID);

        DB::transaction(function () use ($dokumentasiAsetLain, $asetLain): void {
            $dokumentasiAsetLain->delete();
            $this->dokumentasiCheck((int) $asetLain->AsetLainID);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data dokumentasi aset lain-lain berhasil dihapus.',
        ]);
    }
}

