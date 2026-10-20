<?php

namespace App\Http\Controllers\SetaraKasController;

use App\Http\Controllers\Controller;
use App\Models\JwbKasus;
use App\Models\SetaraKas\CekSaldoSetaraKas;
use App\Models\SetaraKas\RekonsilasiBankSetaraKas;
use App\Models\SetaraKas\RincianRekonsiliasiBankSetaraKas;
use App\Models\SetaraKas\SetaraKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekonsilasiBankSetaraKasController extends Controller
{
    protected function resolveAuthorizedSetaraKas(Request $request, int $setaraKasId): SetaraKas
    {
        $setaraKas = SetaraKas::findOrFail($setaraKasId);

        JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $setaraKas->JwbKasusID)
            ->firstOrFail();

        return $setaraKas;
    }

    private function rekonsilasiCheck(SetaraKas $setaraKas): void
    {
        $hasRekonsilasi = $setaraKas->rekonsilasiBank()->exists();

        $setaraKas->updateQuietly([
            'RekonsilasiCheck' => $hasRekonsilasi,
        ]);
    }

    protected function serializeItem(RekonsilasiBankSetaraKas $item): array
    {
        $item->loadMissing(['cekSaldo', 'rincian']);

        $rincian = $item->rincian->sortBy('RincianRekonsiliasiBankID');

        $mapRow = fn (RincianRekonsiliasiBankSetaraKas $row) => [
            'id' => $row->RincianRekonsiliasiBankID,
            'description' => $row->Keterangan ?? '',
            'amount' => (int) $row->Nominal,
        ];

        return [
            'id' => $item->RekonsilasiBankID,
            'RekonsilasiBankID' => $item->RekonsilasiBankID,
            'SetaraKasID' => $item->SetaraKasID,
            'CekSaldoID' => $item->CekSaldoID,
            'name' => $item->cekSaldo?->NamaBank ?? '',
            'NamaBank' => $item->cekSaldo?->NamaBank ?? '',
            'bankBalance' => (int) ($item->cekSaldo?->SaldoRekeningKoran ?? 0),
            'bookBalance' => (int) ($item->cekSaldo?->SaldoBukuBesar ?? 0),
            'SaldoSetelahRekonBank' => (int) $item->SaldoSetelahRekonBank,
            'SaldoSetelahRekonBuku' => (int) $item->SaldoSetelahRekonBuku,
            'Selisih' => (int) $item->Selisih,
            'Status' => (bool) $item->Status,
            'isBalanced' => (bool) $item->Status,
            'bankAdditions' => $rincian->where('Kategori', 'BankBertambah')->values()->map($mapRow)->all(),
            'bankReductions' => $rincian->where('Kategori', 'BankBerkurang')->values()->map($mapRow)->all(),
            'bookAdditions' => $rincian->where('Kategori', 'BukuBertambah')->values()->map($mapRow)->all(),
            'bookReductions' => $rincian->where('Kategori', 'BukuBerkurang')->values()->map($mapRow)->all(),
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
        ]);

        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $validated['SetaraKasID']);

        $items = $setaraKas->rekonsilasiBank()
            ->with(['cekSaldo', 'rincian'])
            ->orderBy('RekonsilasiBankID')
            ->get()
            ->map(fn (RekonsilasiBankSetaraKas $item) => $this->serializeItem($item))
            ->values();

        $availableBanks = $setaraKas->cekSaldoSetaraKas()
            ->orderBy('CekSaldoID')
            ->get()
            ->map(fn (CekSaldoSetaraKas $cs) => [
                'CekSaldoID' => $cs->CekSaldoID,
                'NamaBank' => $cs->NamaBank ?? '',
                'SaldoRekeningKoran' => (int) $cs->SaldoRekeningKoran,
                'SaldoBukuBesar' => (int) $cs->SaldoBukuBesar,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'availableBanks' => $availableBanks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'SetaraKasID' => ['required', 'integer', 'exists:setara_kas,SetaraKasID'],
            'CekSaldoID' => ['required', 'integer', 'exists:cek_saldo_setara_kas,CekSaldoID'],
            'bankAdditions' => ['nullable', 'array'],
            'bankAdditions.*.description' => ['nullable', 'string', 'max:255'],
            'bankAdditions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'bankReductions' => ['nullable', 'array'],
            'bankReductions.*.description' => ['nullable', 'string', 'max:255'],
            'bankReductions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'bookAdditions' => ['nullable', 'array'],
            'bookAdditions.*.description' => ['nullable', 'string', 'max:255'],
            'bookAdditions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'bookReductions' => ['nullable', 'array'],
            'bookReductions.*.description' => ['nullable', 'string', 'max:255'],
            'bookReductions.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $validated['SetaraKasID']);

        $cekSaldo = CekSaldoSetaraKas::where('SetaraKasID', $setaraKas->SetaraKasID)
            ->where('CekSaldoID', $validated['CekSaldoID'])
            ->firstOrFail();

        $rekonsilasi = DB::transaction(function () use ($setaraKas, $cekSaldo, $validated): RekonsilasiBankSetaraKas {
            $bankAdditions = collect($validated['bankAdditions'] ?? [])
                ->filter(fn ($r) => !empty(trim($r['description'] ?? '')) || (int)($r['amount'] ?? 0) > 0)
                ->values();
            $bankReductions = collect($validated['bankReductions'] ?? [])
                ->filter(fn ($r) => !empty(trim($r['description'] ?? '')) || (int)($r['amount'] ?? 0) > 0)
                ->values();
            $bookAdditions = collect($validated['bookAdditions'] ?? [])
                ->filter(fn ($r) => !empty(trim($r['description'] ?? '')) || (int)($r['amount'] ?? 0) > 0)
                ->values();
            $bookReductions = collect($validated['bookReductions'] ?? [])
                ->filter(fn ($r) => !empty(trim($r['description'] ?? '')) || (int)($r['amount'] ?? 0) > 0)
                ->values();

            // 1. Ambil saldo awal dari Cek Saldo
            $saldoBank = (int) $cekSaldo->SaldoRekeningKoran;
            $saldoBuku = (int) $cekSaldo->SaldoBukuBesar;

            // 2. Hitung total masing-masing kelompok kategori
            $totalBankAdditions = (int) $bankAdditions->sum('amount');
            $totalBankReductions = (int) $bankReductions->sum('amount');
            $totalBookAdditions = (int) $bookAdditions->sum('amount');
            $totalBookReductions = (int) $bookReductions->sum('amount');

            // 3. Hitung saldo setelah rekonsiliasi
            $saldoSetelahRekonBank = $saldoBank + $totalBankAdditions - $totalBankReductions;
            $saldoSetelahRekonBuku = $saldoBuku + $totalBookAdditions - $totalBookReductions;

            // 4. Hitung selisih & status seimbang
            $selisih = abs($saldoSetelahRekonBank - $saldoSetelahRekonBuku);
            $status = ($saldoSetelahRekonBank === $saldoSetelahRekonBuku);

            // 5. Simpan / Perbarui master RekonsilasiBank
            $record = RekonsilasiBankSetaraKas::updateOrCreate(
                [
                    'SetaraKasID' => $setaraKas->SetaraKasID,
                    'CekSaldoID' => $cekSaldo->CekSaldoID,
                ],
                [
                    'SaldoSetelahRekonBank' => $saldoSetelahRekonBank,
                    'SaldoSetelahRekonBuku' => $saldoSetelahRekonBuku,
                    'Selisih' => $selisih,
                    'Status' => $status,
                ]
            );

            // 6. Sinkronisasi rincian items
            $record->rincian()->delete();

            $rincianData = [];
            foreach ($bankAdditions as $item) {
                $rincianData[] = [
                    'Kategori' => 'BankBertambah',
                    'Keterangan' => trim($item['description'] ?? ''),
                    'Nominal' => (int) ($item['amount'] ?? 0),
                ];
            }
            foreach ($bankReductions as $item) {
                $rincianData[] = [
                    'Kategori' => 'BankBerkurang',
                    'Keterangan' => trim($item['description'] ?? ''),
                    'Nominal' => (int) ($item['amount'] ?? 0),
                ];
            }
            foreach ($bookAdditions as $item) {
                $rincianData[] = [
                    'Kategori' => 'BukuBertambah',
                    'Keterangan' => trim($item['description'] ?? ''),
                    'Nominal' => (int) ($item['amount'] ?? 0),
                ];
            }
            foreach ($bookReductions as $item) {
                $rincianData[] = [
                    'Kategori' => 'BukuBerkurang',
                    'Keterangan' => trim($item['description'] ?? ''),
                    'Nominal' => (int) ($item['amount'] ?? 0),
                ];
            }

            if (!empty($rincianData)) {
                $record->rincian()->createMany($rincianData);
            }

            $this->rekonsilasiCheck($setaraKas);

            return $record->fresh(['cekSaldo', 'rincian']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi bank berhasil disimpan.',
            'data' => $this->serializeItem($rekonsilasi),
        ], 201);
    }

    public function show(Request $request, RekonsilasiBankSetaraKas $rekonsilasiBankSetaraKa): JsonResponse
    {
        $this->resolveAuthorizedSetaraKas($request, (int) $rekonsilasiBankSetaraKa->SetaraKasID);

        return response()->json([
            'success' => true,
            'data' => $this->serializeItem($rekonsilasiBankSetaraKa),
        ]);
    }

    public function update(Request $request, RekonsilasiBankSetaraKas $rekonsilasiBankSetaraKa): JsonResponse
    {
        $request->merge([
            'SetaraKasID' => $rekonsilasiBankSetaraKa->SetaraKasID,
            'CekSaldoID' => $request->input('CekSaldoID', $rekonsilasiBankSetaraKa->CekSaldoID),
        ]);

        return $this->store($request);
    }

    public function destroy(Request $request, RekonsilasiBankSetaraKas $rekonsilasiBankSetaraKa): JsonResponse
    {
        $setaraKas = $this->resolveAuthorizedSetaraKas($request, (int) $rekonsilasiBankSetaraKa->SetaraKasID);

        DB::transaction(function () use ($rekonsilasiBankSetaraKa, $setaraKas): void {
            $rekonsilasiBankSetaraKa->delete();
            $this->rekonsilasiCheck($setaraKas);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data rekonsiliasi bank berhasil dihapus.',
        ]);
    }
}

