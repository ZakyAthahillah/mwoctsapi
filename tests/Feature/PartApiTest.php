<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_parts_with_pagination(): void
    {
        $user = User::factory()->create();
        Part::factory()->count(12)->create();
        Part::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_parts_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Part::factory()->forArea($area)->count(2)->create();
        Part::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_part_detail(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->create([
            'code' => 'PRT001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts/'.$part->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'PRT001');
    }

    public function test_authenticated_user_can_create_part(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/parts', [
                'area_id' => $area->id,
                'code' => 'PRT001',
                'name' => 'Part A',
                'description' => 'Deskripsi part',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Part created successfully')
            ->assertJsonPath('data.code', 'PRT001');

        $this->assertDatabaseHas('parts', [
            'code' => 'PRT001',
            'name' => 'Part A',
            'area_id' => $area->id,
        ]);
    }

    public function test_create_part_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/parts', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_part(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $part = Part::factory()->create([
            'code' => 'PRT001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/parts/'.$part->id, [
                'area_id' => $area->id,
                'code' => 'PRT002',
                'name' => 'Part B',
                'description' => 'Deskripsi baru',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Part updated successfully')
            ->assertJsonPath('data.code', 'PRT002');
    }

    public function test_authenticated_user_cannot_update_deleted_part(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/parts/'.$part->id, [
                'area_id' => null,
                'code' => 'PRT002',
                'name' => 'Part B',
                'description' => null,
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Part has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_part_using_status_flag(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/parts/'.$part->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Part deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'status' => 99,
        ]);
    }

    public function test_delete_part_returns_error_when_part_already_deleted(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/parts/'.$part->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Part has already been deleted.');
    }
}
