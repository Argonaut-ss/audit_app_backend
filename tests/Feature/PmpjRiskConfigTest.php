<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmpjRiskConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_pmpj_risk_profiles_from_backend(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pmpj/risk-config');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'profile_name',
                    'categories',
                    'risk_map',
                ],
            ],
        ]);

        $response->assertJsonPath('data.0.profile_name', 'Profil Pengguna Jasa');
        $response->assertJsonPath('data.0.categories.0', 'Direksi, Komisaris dan Pejabat Struktural lainnya pada BUMN/BUMD');
        $response->assertJsonPath('data.0.risk_map.Tinggi', 'Direksi, Komisaris dan Pejabat Struktural lainnya pada BUMN/BUMD');
    }
}
