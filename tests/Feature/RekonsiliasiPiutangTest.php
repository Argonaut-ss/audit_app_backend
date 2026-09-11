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

        $piutang = Piutang::create(['JwbKasusID' => $jwbKasus->JwbKasusID]);

        return [$user, $jwbKasus, $piutang];
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

    protected function reconciliationPayload(KonfirmasiPiutang $konfirmasiPiutang): array
    {
        return [
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-99/65',
            'TanggalFaktur' => '2024-12-25',
            'SaldoBuku' => 500000000,
            'SaldoCustomer' => 500000000,
            'Selisih' => 0,
            'Keterangan' => 'sesuai',
        ];
    }

    public function test_mahasiswa_can_store_rekonsiliasi_using_own_konfirmasi_piutang(): void
    {
        [$user, , $piutang] = $this->createPiutangForNewUser();
        $konfirmasiPiutang = $this->createKonfirmasiPiutang($piutang, 'Toko Kebak');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                '/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang',
                $this->reconciliationPayload($konfirmasiPiutang)
            );

        $response->assertCreated();
        $response->assertJsonPath('data.KonfirmasiPiutangID', $konfirmasiPiutang->KonfirmasiPiutangID);
        $response->assertJsonPath('data.NamaCustomer', 'Toko Kebak');
        $this->assertDatabaseHas('rekonsiliasi_piutang', [
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-99/65',
        ]);
    }

    public function test_index_returns_confirmation_identity_options_and_derived_customer_name(): void
    {
        [$user, , $piutang] = $this->createPiutangForNewUser();
        $firstKonfirmasi = $this->createKonfirmasiPiutang($piutang, 'Toko Kebak');
        $secondKonfirmasi = $this->createKonfirmasiPiutang($piutang, 'Toko Makmur Jaya');

        RekonsiliasiPiutang::create([
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $secondKonfirmasi->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-10/01',
            'TanggalFaktur' => '2024-12-25',
            'SaldoBuku' => 100000,
            'SaldoCustomer' => 100000,
            'Selisih' => 0,
            'Keterangan' => 'sesuai',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang');

        $response->assertOk();
        $response->assertJsonPath('customer_options.0.value', $firstKonfirmasi->KonfirmasiPiutangID);
        $response->assertJsonPath('customer_options.0.label', 'Toko Kebak');
        $response->assertJsonPath('customer_options.1.value', $secondKonfirmasi->KonfirmasiPiutangID);
        $response->assertJsonPath('data.0.KonfirmasiPiutangID', $secondKonfirmasi->KonfirmasiPiutangID);
        $response->assertJsonPath('data.0.NamaCustomer', 'Toko Makmur Jaya');
    }

    public function test_index_returns_the_latest_customer_name_from_konfirmasi_piutang(): void
    {
        [$user, , $piutang] = $this->createPiutangForNewUser();
        $konfirmasiPiutang = $this->createKonfirmasiPiutang($piutang, 'Toko Nama Lama');

        RekonsiliasiPiutang::create([
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $konfirmasiPiutang->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-11/01',
            'TanggalFaktur' => '2024-12-26',
            'SaldoBuku' => 100000,
            'SaldoCustomer' => 100000,
            'Selisih' => 0,
            'Keterangan' => 'sesuai',
        ]);

        $konfirmasiPiutang->update(['NamaCustomer' => 'Toko Nama Baru']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang');

        $response->assertOk();
        $response->assertJsonPath('data.0.KonfirmasiPiutangID', $konfirmasiPiutang->KonfirmasiPiutangID);
        $response->assertJsonPath('data.0.NamaCustomer', 'Toko Nama Baru');
        $response->assertJsonPath('customer_options.0.label', 'Toko Nama Baru');
    }

    public function test_store_rejects_missing_or_another_piutangs_konfirmasi_piutang(): void
    {
        [$user, , $piutang] = $this->createPiutangForNewUser();
        [, , $otherPiutang] = $this->createPiutangForNewUser();
        $otherKonfirmasi = $this->createKonfirmasiPiutang($otherPiutang, 'Toko Lain');

        $missingIdResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang', [
                'NomorFaktur' => 'CR-01/01',
                'TanggalFaktur' => '2024-12-25',
                'SaldoBuku' => 100000,
                'SaldoCustomer' => 100000,
                'Selisih' => 0,
            ]);

        $missingIdResponse->assertUnprocessable();
        $missingIdResponse->assertJsonValidationErrors('KonfirmasiPiutangID');

        $otherPiutangResponse = $this->actingAs($user, 'sanctum')
            ->postJson(
                '/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang',
                $this->reconciliationPayload($otherKonfirmasi)
            );

        $otherPiutangResponse->assertNotFound();
    }

    public function test_update_can_relink_rekonsiliasi_to_another_confirmation_for_the_same_piutang(): void
    {
        [$user, , $piutang] = $this->createPiutangForNewUser();
        $firstKonfirmasi = $this->createKonfirmasiPiutang($piutang, 'Toko Awal');
        $secondKonfirmasi = $this->createKonfirmasiPiutang($piutang, 'Toko Terbaru');

        $rekonsiliasiPiutang = RekonsiliasiPiutang::create([
            'PiutangID' => $piutang->PiutangID,
            'KonfirmasiPiutangID' => $firstKonfirmasi->KonfirmasiPiutangID,
            'NomorFaktur' => 'CR-02/02',
            'TanggalFaktur' => '2024-12-25',
            'SaldoBuku' => 100000,
            'SaldoCustomer' => 100000,
            'Selisih' => 0,
            'Keterangan' => 'sesuai',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson(
                '/api/piutang/' . $piutang->PiutangID . '/rekonsiliasi-piutang/' . $rekonsiliasiPiutang->RekonsiliasiPiutangID,
                [
                    'KonfirmasiPiutangID' => $secondKonfirmasi->KonfirmasiPiutangID,
                    'Keterangan' => 'diperbarui',
                ]
            );

        $response->assertOk();
        $response->assertJsonPath('data.KonfirmasiPiutangID', $secondKonfirmasi->KonfirmasiPiutangID);
        $response->assertJsonPath('data.NamaCustomer', 'Toko Terbaru');
        $response->assertJsonPath('data.Keterangan', 'diperbarui');
    }
}
