<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Group;
use App\Models\Informant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_informants_with_pagination(): void
    {
        $user = User::factory()->create();
        Informant::factory()->count(12)->create();
        Informant::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/informants?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_informants_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Informant::factory()->forArea($area)->count(2)->create();
        Informant::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/informants?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_informant_detail(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create([
            'name' => 'Area Pelapor',
        ]);
        $group = Group::factory()->forArea($area)->create([
            'name' => 'Group Pelapor',
        ]);
        $informant = Informant::factory()->create([
            'area_id' => $area->id,
            'code' => 'INF001',
            'group_id' => $group->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/informants/'.$informant->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'INF001')
            ->assertJsonPath('data.area_name', 'Area Pelapor')
            ->assertJsonPath('data.group_name', 'Group Pelapor');
    }

    public function test_admin_can_create_informant(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/informants', [
                'area_id' => $area->id,
                'code' => 'INF001',
                'name' => 'Pelapor A',
                'status' => 1,
                'group_id' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Informant created successfully')
            ->assertJsonPath('data.code', 'INF001');

        $this->assertDatabaseHas('informants', [
            'code' => 'INF001',
            'name' => 'Pelapor A',
            'area_id' => $area->id,
            'group_id' => 5,
        ]);
    }

    public function test_create_informant_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/informants', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_create_informant(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/informants', [
                'area_id' => null,
                'code' => 'INF001',
                'name' => 'Pelapor A',
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Informant created successfully');
    }

    public function test_admin_can_update_informant(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $informant = Informant::factory()->create([
            'code' => 'INF001',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/informants/'.$informant->id, [
                'area_id' => $area->id,
                'code' => 'INF002',
                'name' => 'Pelapor B',
                'status' => 1,
                'group_id' => 9,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Informant updated successfully')
            ->assertJsonPath('data.code', 'INF002');
    }

    public function test_admin_cannot_update_deleted_informant(): void
    {
        $admin = User::factory()->admin()->create();
        $informant = Informant::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/informants/'.$informant->id, [
                'area_id' => null,
                'code' => 'INF002',
                'name' => 'Pelapor B',
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Informant has been deleted and cannot be updated.');
    }

    public function test_admin_can_delete_informant_using_status_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $informant = Informant::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/informants/'.$informant->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Informant deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('informants', [
            'id' => $informant->id,
            'status' => 99,
        ]);
    }

    public function test_delete_informant_returns_error_when_informant_already_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $informant = Informant::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/informants/'.$informant->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Informant has already been deleted.');
    }
}
