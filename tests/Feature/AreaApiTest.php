<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_areas_with_pagination(): void
    {
        $user = User::factory()->create();
        Area::factory()->count(12)->create();
        Area::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/areas?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_view_area_detail(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create([
            'code' => 'AREA01',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/areas/'.$area->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'AREA01');
    }

    public function test_admin_can_create_area(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/areas', [
                'code' => 'AREA01',
                'name' => 'Jakarta Barat',
                'object_name' => 'Objek Area',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Area created successfully')
            ->assertJsonPath('data.code', 'AREA01');

        $this->assertDatabaseHas('areas', [
            'code' => 'AREA01',
            'name' => 'Jakarta Barat',
        ]);
    }

    public function test_create_area_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/areas', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'object_name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_create_area(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/areas', [
                'code' => 'AREA01',
                'name' => 'Jakarta Barat',
                'object_name' => 'Objek Area',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Area created successfully');
    }

    public function test_admin_can_update_area(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create([
            'code' => 'AREA01',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/areas/'.$area->id, [
                'code' => 'AREA02',
                'name' => 'Jakarta Selatan',
                'object_name' => 'Objek Update',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Area updated successfully')
            ->assertJsonPath('data.code', 'AREA02');
    }

    public function test_admin_cannot_update_deleted_area(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->deletedStatus()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/areas/'.$area->id, [
                'code' => 'AREA02',
                'name' => 'Jakarta Selatan',
                'object_name' => 'Objek Update',
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Area has been deleted and cannot be updated.');
    }

    public function test_admin_can_delete_area_using_status_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create([
            'status' => 1,
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/areas/'.$area->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Area deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('areas', [
            'id' => $area->id,
            'status' => 99,
        ]);
    }

    public function test_delete_area_returns_error_when_area_already_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->deletedStatus()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/areas/'.$area->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Area has already been deleted.');
    }
}
