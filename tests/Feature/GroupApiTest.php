<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_groups_with_pagination(): void
    {
        $user = User::factory()->create();
        Group::factory()->count(12)->create();
        Group::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/groups?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_groups_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Group::factory()->forArea($area)->count(2)->create();
        Group::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/groups?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_group_detail(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'name' => 'Group Alpha',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/groups/'.$group->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Group Alpha');
    }

    public function test_admin_can_create_group(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/groups', [
                'area_id' => $area->id,
                'name' => 'Group Alpha',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group created successfully')
            ->assertJsonPath('data.name', 'Group Alpha');

        $this->assertDatabaseHas('groups', [
            'name' => 'Group Alpha',
            'area_id' => $area->id,
        ]);
    }

    public function test_create_group_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/groups', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_create_group(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/groups', [
                'area_id' => null,
                'name' => 'Group Alpha',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group created successfully');
    }

    public function test_admin_can_update_group(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $group = Group::factory()->create([
            'name' => 'Group Alpha',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/groups/'.$group->id, [
                'area_id' => $area->id,
                'name' => 'Group Beta',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group updated successfully')
            ->assertJsonPath('data.name', 'Group Beta');
    }

    public function test_admin_cannot_update_deleted_group(): void
    {
        $admin = User::factory()->admin()->create();
        $group = Group::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/groups/'.$group->id, [
                'area_id' => null,
                'name' => 'Group Beta',
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Group has been deleted and cannot be updated.');
    }

    public function test_admin_can_delete_group_using_status_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $group = Group::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/groups/'.$group->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Group deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'status' => 99,
        ]);
    }

    public function test_delete_group_returns_error_when_group_already_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $group = Group::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/groups/'.$group->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Group has already been deleted.');
    }
}
