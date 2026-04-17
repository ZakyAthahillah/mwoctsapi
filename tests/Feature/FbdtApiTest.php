<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Fbdt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FbdtApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_fbdt_years_with_pagination(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 1]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 2]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2025, 'bulan' => 1]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/fbdts?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.year', 2026)
            ->assertJsonPath('data.0.area_name', $area->name)
            ->assertJsonPath('data.0.months_count', 2);
    }

    public function test_authenticated_user_can_view_fbdt_detail(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 1, 'fb' => 10]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 2, 'fb' => 20]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/fbdts/2026');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.area_name', $area->name)
            ->assertJsonPath('data.months.0.month', 1)
            ->assertJsonPath('data.months.0.fb', 10);
    }

    public function test_authenticated_user_can_create_fbdt_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/fbdts', [
                'area_id' => $area->id,
                'year' => 2026,
                'targets' => [
                    ['month' => 1, 'fb' => 10, 'dt' => 20, 'mtbf' => 30, 'mttr' => 40],
                    ['month' => 2, 'fb' => 11, 'dt' => 21, 'mtbf' => 31, 'mttr' => 41],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.months.1.month', 2);

        $this->assertDatabaseHas('fbdts', [
            'area_id' => $area->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]);
    }

    public function test_create_fbdt_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/fbdts', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['area_id', 'year', 'targets'],
            ]);
    }

    public function test_authenticated_user_can_update_fbdt_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create();
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 1, 'fb' => 10]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/fbdts/2026', [
                'area_id' => $area->id,
                'targets' => [
                    ['month' => 1, 'fb' => 15, 'dt' => 25, 'mtbf' => 35, 'mttr' => 45],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.months.0.fb', 15);
    }

    public function test_authenticated_user_can_check_fbdt_year_existence(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create();
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 1]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/fbdts/check?area_id='.$area->id.'&year=2026');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exists', true);
    }

    public function test_fbdt_detail_returns_not_found_when_year_does_not_exist(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/fbdts/2026?area_id=1');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found');
    }
}
