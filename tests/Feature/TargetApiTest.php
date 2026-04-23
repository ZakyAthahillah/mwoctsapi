<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TargetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_list_view_update_and_delete_target(): void
    {
        $fixtures = $this->fixtures();
        $token = auth('api')->login($fixtures['user']);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/targets', [
                'year' => 2026,
                'part_id' => $fixtures['part']->id,
                'targets' => [
                    ['month' => 1, 'mtbf' => 120, 'mttr' => 30],
                    ['month' => 2, 'mtbf' => 130, 'mttr' => 28],
                ],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Target created successfully')
            ->assertJsonPath('data.part_id', (string) $fixtures['part']->id)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.total_month', 2)
            ->assertJsonPath('data.targets.0.month', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/targets?per_page=10&year=2026&search=Part')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.part_name', 'Part A')
            ->assertJsonPath('data.0.average_mtbf', 125);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/targets/'.$fixtures['part']->id.'/2026')
            ->assertOk()
            ->assertJsonPath('data.targets.1.month', 2)
            ->assertJsonPath('data.targets.1.mttr', 28);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/targets/'.$fixtures['part']->id.'/2026', [
                'targets' => [
                    ['month' => 1, 'mtbf' => 140, 'mttr' => 25],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Target updated successfully')
            ->assertJsonPath('data.total_month', 1)
            ->assertJsonPath('data.targets.0.mtbf', 140);

        $this->assertDatabaseMissing('target_models', [
            'area_id' => $fixtures['area']->id,
            'part_id' => $fixtures['part']->id,
            'tahun' => 2026,
            'bulan' => 2,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/targets/'.$fixtures['part']->id.'/2026')
            ->assertOk()
            ->assertJsonPath('message', 'Target deleted successfully');

        $this->assertDatabaseMissing('target_models', [
            'area_id' => $fixtures['area']->id,
            'part_id' => $fixtures['part']->id,
            'tahun' => 2026,
        ]);
    }

    public function test_authenticated_user_can_check_target_existence(): void
    {
        $fixtures = $this->fixtures();
        $this->insertTarget($fixtures);
        $token = auth('api')->login($fixtures['user']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/targets/check?year=2026&part_id='.$fixtures['part']->id)
            ->assertOk()
            ->assertJsonPath('data.exists', true);
    }

    public function test_target_is_scoped_to_authenticated_user_area(): void
    {
        $fixtures = $this->fixtures();
        $otherFixtures = $this->fixtures();
        $this->insertTarget($fixtures);
        $this->insertTarget($otherFixtures);
        $token = auth('api')->login($fixtures['user']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/targets?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.part_id', (string) $fixtures['part']->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/targets/'.$otherFixtures['part']->id.'/2026')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Resource not found');
    }

    public function test_target_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/targets', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['year', 'part_id', 'targets'],
            ]);
    }

    public function test_target_requires_authentication(): void
    {
        $this->getJson('/api/targets')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function fixtures(): array
    {
        $area = Area::factory()->create();

        return [
            'area' => $area,
            'user' => User::factory()->create(['area_id' => $area->id]),
            'part' => Part::factory()->forArea($area)->create(['name' => 'Part A']),
        ];
    }

    private function insertTarget(array $fixtures, array $overrides = []): void
    {
        DB::table('target_models')->insert([
            'area_id' => $fixtures['area']->id,
            'part_id' => $fixtures['part']->id,
            'tahun' => 2026,
            'bulan' => 1,
            'mtbf' => 120,
            'mttr' => 30,
            ...$overrides,
        ]);
    }
}
