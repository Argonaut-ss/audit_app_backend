<?php

namespace Tests\Feature;

use App\Models\COA;
use App\Models\DataClient;
use App\Models\JwbKasus;
use App\Models\Kasus;
use App\Models\Mahasiswa;
use App\Models\Piutang\JurnalKoreksi as JurnalKoreksiPiutang;
use App\Models\Piutang\Piutang;
use App\Models\SetaraKas\JurnalKoreksiSetaraKas;
use App\Models\SetaraKas\SetaraKas;
use App\Models\User;
use App\Models\UtangUsaha\JurnalKoreksiUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TopScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected function createMahasiswaContext(): array
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $mahasiswa = Mahasiswa::create([
            'user_id' => $user->id,
            'nim' => fake()->unique()->numerify('27########'),
        ]);

        $kasus = Kasus::first();
        if (! $kasus) {
            $client = DataClient::first() ?? DataClient::create([
                'NamaClient' => 'PT Test Client',
                'TipeClient' => 'Swasta',
                'Alamat' => 'Jl. Test No. 1',
                'NoTelp' => '08123456789',
            ]);

            $kasus = Kasus::create([
                'ClientID' => $client->ClientID,
                'NamaTugas' => 'Tugas Kasus Audit',
                'NamaFile' => 'kasus_test.pdf',
                'File' => 'dummy-content',
            ]);
        }

        $jwbKasus = JwbKasus::create([
            'MahasiswasID' => $mahasiswa->id,
            'KasusID' => $kasus->KasusID,
            'JenisPerusahaan' => 'Dagang',
            'Periode' => '2022-12-31',
            'WaktuMulai' => '2024-01-10',
            'BatasWaktu' => '2024-02-10',
            'Nilai' => 1000000,
        ]);

        return [
            'user' => $user,
            'mahasiswa' => $mahasiswa,
            'jwbKasus' => $jwbKasus,
        ];
    }

    public function test_unauthenticated_user_cannot_access_top_schedule(): void
    {
        $response = $this->getJson('/api/top-schedule/99999');
        $response->assertUnauthorized();
    }

    public function test_mahasiswa_b_cannot_access_top_schedule_of_mahasiswa_a(): void
    {
        $mahasiswaA = $this->createMahasiswaContext();
        $mahasiswaB = $this->createMahasiswaContext();

        $response = $this->actingAs($mahasiswaB['user'], 'sanctum')
            ->getJson('/api/top-schedule/' . $mahasiswaA['jwbKasus']->JwbKasusID . '?top=A');

        $response->assertNotFound();
    }

    public function test_get_top_schedule_default_initializes_with_coa_mapping(): void
    {
        $ctx = $this->createMahasiswaContext();

        // 1. Buat akun COA untuk siklus Kas dan Setara Kas
        $coaKasDiBank = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1101',
            'NamaAkun' => 'Kas di Bank',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Kas dan Setara Kas',
            'Saldo' => 'Debit',
            'PerBook' => 1000000000,
            'AuditSebelum' => 900000000,
        ]);

        $coaBankABC = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1102-01',
            'NamaAkun' => 'Bank ABC',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Rincian Setara Kas - Bank',
            'Saldo' => 'Debit',
            'PerBook' => 500000000,
            'AuditSebelum' => 450000000,
        ]);

        // 2. Mahasiswa membuka TOP A
        $response = $this->actingAs($ctx['user'], 'sanctum')
            ->getJson('/api/top-schedule/' . $ctx['jwbKasus']->JwbKasusID . '?top=A');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.top', 'A');
        $response->assertJsonPath('data.mapping', 'Kas dan Setara Kas');

        // Pastikan terbentuk 2 sub-tabel sesuai SubMappingTop
        $response->assertJsonCount(2, 'data.tables');

        // Tabel 1: Kas dan Setara Kas
        $response->assertJsonPath('data.tables.0.sub_mapping', 'Kas dan Setara Kas');
        $response->assertJsonPath('data.tables.0.rows.0.NamaAkun', 'Kas di Bank');
        $response->assertJsonPath('data.tables.0.rows.0.PerBook', 1000000000);
        $response->assertJsonPath('data.tables.0.rows.0.AJE_RJE', 0);
        $response->assertJsonPath('data.tables.0.rows.0.AuditedBerjalan', 1000000000);

        // Tabel 2: Rincian Setara Kas - Bank
        $response->assertJsonPath('data.tables.1.sub_mapping', 'Rincian Setara Kas - Bank');
        $response->assertJsonPath('data.tables.1.rows.0.NamaAkun', 'Bank ABC');
        $response->assertJsonPath('data.tables.1.rows.0.PerBook', 500000000);
    }

    public function test_multi_module_aje_rje_aggregation_and_modal_breakdown(): void
    {
        $ctx = $this->createMahasiswaContext();

        // Akun Bank ABC (Saldo Normal: Debit)
        $coaBankABC = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1102-01',
            'NamaAkun' => 'Bank ABC',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Rincian Setara Kas - Bank',
            'Saldo' => 'Debit',
            'PerBook' => 2500000000,
            'AuditSebelum' => 1950000000,
        ]);

        // Jurnal Koreksi di Modul Setara Kas (Debit 100jt)
        $setaraKas = SetaraKas::create(['JwbKasusID' => $ctx['jwbKasus']->JwbKasusID]);
        $jkSetaraKas = JurnalKoreksiSetaraKas::create([
            'SetaraKasID' => $setaraKas->SetaraKasID,
            'Keterangan' => 'Koreksi jasa giro bank',
        ]);
        $jkSetaraKas->pembayaranJurnalKoreksi()->create([
            'COAID' => $coaBankABC->COAID,
            'Debet' => 100000000,
            'Kredit' => 0,
        ]);

        // Jurnal Koreksi di Modul Piutang (Kredit 50jt)
        $piutang = Piutang::create(['JwbKasusID' => $ctx['jwbKasus']->JwbKasusID]);
        $jkPiutang = JurnalKoreksiPiutang::create([
            'PiutangID' => $piutang->PiutangID,
            'Keterangan' => 'Koreksi pelunasan piutang via transfer',
        ]);
        $jkPiutang->pembayaranJurnalKoreksi()->create([
            'COAID' => $coaBankABC->COAID,
            'Debet' => 0,
            'Kredit' => 50000000,
        ]);

        // Jurnal Koreksi di Modul Utang Usaha (Debit 20jt)
        $utangUsaha = UtangUsaha::create(['JwbKasusID' => $ctx['jwbKasus']->JwbKasusID]);
        $jkUtangUsaha = JurnalKoreksiUtangUsaha::create([
            'UtangUsahaID' => $utangUsaha->UtangUsahaID,
            'Keterangan' => 'Koreksi pembayaran utang',
        ]);
        $jkUtangUsaha->pembayaranJurnalKoreksi()->create([
            'COAID' => $coaBankABC->COAID,
            'Debet' => 20000000,
            'Kredit' => 0,
        ]);

        // Request TOP A
        $response = $this->actingAs($ctx['user'], 'sanctum')
            ->getJson('/api/top-schedule/' . $ctx['jwbKasus']->JwbKasusID . '?top=A');

        $response->assertOk();

        // Cari row Bank ABC
        $tableBank = collect($response->json('data.tables'))
            ->firstWhere('sub_mapping', 'Rincian Setara Kas - Bank');
        $rowBank = collect($tableBank['rows'])
            ->firstWhere('COAID', $coaBankABC->COAID);

        // Verifikasi kalkulasi agregasi:
        // Total Debit = 100jt + 20jt = 120jt
        // Total Kredit = 50jt
        // AJE/RJE (karena Debit) = 120jt - 50jt = 70jt
        // Audited Berjalan = 2.500.000.000 + 70.000.000 = 2.570.000.000
        $this->assertEquals(120000000, $rowBank['TotalDebit']);
        $this->assertEquals(50000000, $rowBank['TotalKredit']);
        $this->assertEquals(70000000, $rowBank['AJE_RJE']);
        $this->assertEquals(2570000000, $rowBank['AuditedBerjalan']);

        // Verifikasi array rincian_jurnal untuk modal detail (i)
        $this->assertCount(3, $rowBank['rincian_jurnal']);
        $sumberList = collect($rowBank['rincian_jurnal'])->pluck('sumber')->all();
        $this->assertContains('Kas dan Setara Kas', $sumberList);
        $this->assertContains('Piutang', $sumberList);
        $this->assertContains('Utang Usaha', $sumberList);
    }

    public function test_saving_top_schedule_dates_and_custom_items(): void
    {
        $ctx = $this->createMahasiswaContext();

        $coaBank1 = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1101',
            'NamaAkun' => 'Kas di Bank',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Kas dan Setara Kas',
            'Saldo' => 'Debit',
            'PerBook' => 1000000000,
            'AuditSebelum' => 900000000,
        ]);

        $payload = [
            'top' => 'A',
            'TanggalPerBook' => '2022-12-31',
            'TanggalAuditBerjalan' => '2023-01-20',
            'TanggalAuditSebelum' => '2021-12-31',
            'IsFinal' => true,
            'items' => [
                [
                    'COAID' => $coaBank1->COAID,
                    'SubMapping' => 'Kas dan Setara Kas',
                    'Urutan' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($ctx['user'], 'sanctum')
            ->postJson('/api/top-schedule/' . $ctx['jwbKasus']->JwbKasusID, $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.TanggalAuditBerjalan', '2023-01-20');
        $response->assertJsonPath('data.IsFinal', true);

        // Verifikasi di database
        $this->assertDatabaseHas('top_schedules', [
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'Top' => 'A',
            'TanggalAuditBerjalan' => '2023-01-20',
            'IsFinal' => true,
        ]);

        $this->assertDatabaseHas('top_schedule_items', [
            'COAID' => $coaBank1->COAID,
            'SubMapping' => 'Kas dan Setara Kas',
        ]);
    }

    public function test_control_account_rollup_and_available_accounts_contain_full_metrics(): void
    {
        $ctx = $this->createMahasiswaContext();

        // 1. Akun induk kontrol
        $coaKasDiBank = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1-0000',
            'NamaAkun' => 'Kas di Bank',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Kas dan Setara Kas',
            'Saldo' => 'Debit',
            'PerBook' => 10003512720,
            'AuditSebelum' => 8497338192,
        ]);

        // 2. Akun rincian bank
        $coaBankABC = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1-1111',
            'NamaAkun' => 'Bank ABC',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Rincian Setara Kas - Bank',
            'Saldo' => 'Debit',
            'PerBook' => 2500000000,
            'AuditSebelum' => 1950000000,
        ]);

        $coaBankBCD = COA::create([
            'JwbKasusID' => $ctx['jwbKasus']->JwbKasusID,
            'NoAkun' => '1-1112',
            'NamaAkun' => 'Bank BCD',
            'MappingTop' => 'Kas dan Setara Kas',
            'SubMappingTop' => 'Rincian Setara Kas - Bank',
            'Saldo' => 'Debit',
            'PerBook' => 3334504240,
            'AuditSebelum' => 2832446064,
        ]);

        // Jurnal Bank ABC: Kredit 150jt
        $setaraKas = SetaraKas::create(['JwbKasusID' => $ctx['jwbKasus']->JwbKasusID]);
        $jk1 = JurnalKoreksiSetaraKas::create([
            'SetaraKasID' => $setaraKas->SetaraKasID,
            'Keterangan' => 'Koreksi penyesuaian Bank ABC',
        ]);
        $jk1->pembayaranJurnalKoreksi()->create([
            'COAID' => $coaBankABC->COAID,
            'Debet' => 0,
            'Kredit' => 150000000,
        ]);

        // Jurnal Bank BCD: Kredit 50jt
        $jk2 = JurnalKoreksiSetaraKas::create([
            'SetaraKasID' => $setaraKas->SetaraKasID,
            'Keterangan' => 'Koreksi kliring Bank BCD',
        ]);
        $jk2->pembayaranJurnalKoreksi()->create([
            'COAID' => $coaBankBCD->COAID,
            'Debet' => 0,
            'Kredit' => 50000000,
        ]);

        // Request API
        $response = $this->actingAs($ctx['user'], 'sanctum')
            ->getJson('/api/top-schedule/' . $ctx['jwbKasus']->JwbKasusID . '?top=A');

        $response->assertOk();

        // 1. Verifikasi akun kontrol Kas di Bank (1-0000) ter-rollup otomatis
        $table1 = collect($response->json('data.tables'))->firstWhere('sub_mapping', 'Kas dan Setara Kas');
        $rowKasDiBank = collect($table1['rows'])->firstWhere('NoAkun', '1-0000');

        $this->assertEquals(-200000000, $rowKasDiBank['AJE_RJE']);
        $this->assertEquals(9803512720, $rowKasDiBank['AuditedBerjalan']);
        $this->assertCount(2, $rowKasDiBank['rincian_jurnal']);

        // 2. Verifikasi available_accounts menyertakan seluruh properti angka
        $avail = $response->json('data.available_accounts');
        $this->assertNotEmpty($avail);
        $availBankABC = collect($avail)->firstWhere('COAID', $coaBankABC->COAID);
        $this->assertNotNull($availBankABC);
        $this->assertEquals(2500000000, $availBankABC['PerBook']);
        $this->assertEquals(-150000000, $availBankABC['AJE_RJE']);
        $this->assertEquals(2350000000, $availBankABC['AuditedBerjalan']);
        $this->assertNotEmpty($availBankABC['rincian_jurnal']);
    }
}

