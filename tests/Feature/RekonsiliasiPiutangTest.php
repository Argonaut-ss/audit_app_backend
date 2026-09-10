<?php

namespace Tests\Feature;

use App\Models\JwbKasus;
use App\Models\Mahasiswa;
use App\Models\Piutang;
use App\Models\RekonsiliasiPiutang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekonsiliasiPiutangTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_view_and_store_rekonsiliasi_piutang_for_own_piutang(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $mahasiswa = Mahasiswa::create([
            'user_id' => $user->id,
            'nim' => '20240001',
        ]);

        $jwbKasus = JwbKasus::create([
            'MahasiswasID' => $mahasiswa->id,
            'KasusID' => 1,
            'JenisPerusahaan' => 'Dagang',
            'Periode' => '2024-01-01',
            'WaktuMulai' => '2024-01-10',
            'BatasWaktu' => '2024-02-10',
            'Nilai' => 1000000,
        ]);

        $piutang = Piutang::create([
            'JwbKasusID' => $jwbKasus->JwbKasusID,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang');

        $response->assertOk();

        $payload = [
            'NomorFaktur' => 'CR-99/65',
            'NamaCustomer' => 'Toko Kebak',
            'TanggalFaktur' => '2024-12-25',
            'SaldoBuku' => 500000000,
            'SaldoCustomer' => 500000000,
            'Selisih' => 0,
            'Keterangan' => 'sesuai',
        ];

        $storeResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang', $payload);

        $storeResponse->assertCreated();
        $storeResponse->assertJsonPath('data.NomorFaktur', 'CR-99/65');
        $this->assertDatabaseHas('rekonsiliasi_piutang', [
            'PiutangID' => $piutang->PiutangID,
            'NomorFaktur' => 'CR-99/65',
        ]);
    }
}
