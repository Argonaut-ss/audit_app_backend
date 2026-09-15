<?php

namespace Tests\Feature;

use App\Models\JwbKasus;
use App\Models\KonfirmasiPiutang;
use App\Models\Mahasiswa;
use App\Models\Piutang;
use App\Models\RekonsiliasiPiutang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekonsiliasiPiutangTest extends TestCase
{
    use RefreshDatabase;

    protected function createPiutangForNewUser(): array
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $mahasiswa = Mahasiswa::create([
            'user_id' => $user->id,
            'nim' => '2024' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
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

        return [$user, Piutang::create(['JwbKasusID' => $jwbKasus->JwbKasusID])];
    }

    protected function createKonfirmasiPiutang(Piutang $piutang, string $name): KonfirmasiPiutang
    {
        return KonfirmasiPiutang::create([
            'PiutangID' => $piutang->PiutangID,
            'NamaCustomer' => $name,
            'KotaCustomer' => 'Jakarta',
            'Jumlah' => 1000000,
        ]);
    }

    protected function payload(KonfirmasiPiutang $konfirmasiPiutang): array
    {
        return [
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-99/65',
            'TanggalFaktur' => '2024-12-25',
            'SaldoBuku' => 500000000,
            'SaldoCustomer' => 450000000,
            'Selisih' => 50000000,
            'Keterangan' => 'sesuai',
        ];
    }

    public function test_mahasiswa_can_store_rekonsiliasi_using_own_konfirmasi_piutang(): void
    {
        [$user, $piutang] = $this->createPiutangForNewUser();
        $konfirmasiPiutang = $this->createKonfirmasiPiutang($piutang, 'Toko Kebak');

        $response = $this->actingAs($user, 'sanctum')->postJson(
            '/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang',
            $this->payload($konfirmasiPiutang)
        );

        $response->assertCreated();
        $response->assertJsonPath('data.NamaCustomer', 'Toko Kebak');
        $response->assertJsonPath('data.SaldoBuku', 500000000);
        $response->assertJsonPath('data.SaldoCustomer', 450000000);
        $this->assertDatabaseHas('rekonsiliasi_piutang', [
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'SaldoBuku' => 500000000,
            'SaldoCustomer' => 450000000,
        ]);
    }

    public function test_index_uses_the_latest_customer_name_from_konfirmasi_piutang(): void
    {
        [$user, $piutang] = $this->createPiutangForNewUser();
        $konfirmasiPiutang = $this->createKonfirmasiPiutang($piutang, 'Toko Lama');
        RekonsiliasiPiutang::create(array_merge([
            'PiutangID' => $piutang->PiutangID,
        ], $this->payload($konfirmasiPiutang)));
        $konfirmasiPiutang->update(['NamaCustomer' => 'Toko Baru']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang');

        $response->assertOk();
        $response->assertJsonPath('data.0.NamaCustomer', 'Toko Baru');
        $response->assertJsonPath('data.0.SaldoBuku', 500000000);
    }
}
