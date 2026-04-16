<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_positions_with_pagination(): void
    {
        $user = User::factory()->create();
        Position::factory()->count(12)->create();
        Position::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/positions?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_positions_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Position::factory()->forArea($area)->count(2)->create();
        Position::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/positions?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_position_detail(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->create([
            'name' => 'Posisi Alpha',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/positions/'.$position->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Posisi Alpha');
    }

    public function test_authenticated_user_can_create_position(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/positions', [
                'area_id' => $area->id,
                'name' => 'Posisi A',
                'description' => 'Deskripsi posisi',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Position created successfully')
            ->assertJsonPath('data.name', 'Posisi A');

        $this->assertDatabaseHas('positions', [
            'name' => 'Posisi A',
            'area_id' => $area->id,
        ]);
    }

    public function test_create_position_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/positions', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_position(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $position = Position::factory()->create([
            'name' => 'Posisi Alpha',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/positions/'.$position->id, [
                'area_id' => $area->id,
                'name' => 'Posisi Beta',
                'description' => 'Deskripsi update',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Position updated successfully')
            ->assertJsonPath('data.name', 'Posisi Beta');
    }

    public function test_authenticated_user_cannot_update_deleted_position(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/positions/'.$position->id, [
                'area_id' => null,
                'name' => 'Posisi Beta',
                'description' => null,
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Position has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_position_using_status_flag(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/positions/'.$position->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Position deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'status' => 99,
        ]);
    }

    public function test_delete_position_returns_error_when_position_already_deleted(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/positions/'.$position->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Position has already been deleted.');
    }
}
