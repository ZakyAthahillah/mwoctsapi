<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_divisions_with_pagination(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Division::factory()->forArea($area)->count(12)->create();
        Division::factory()->forArea($area)->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/divisions?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_list_division_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Division::factory()->forArea($area)->create(['code' => 'DIV001', 'status' => 1]);
        Division::factory()->forArea($area)->create(['code' => 'DIV002', 'status' => 0]);
        Division::factory()->forArea($area)->create(['code' => 'DIV011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/division_active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_divisions_by_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Division::factory()->forArea($area)->count(2)->create();
        Division::factory()->forArea(Area::factory()->create())->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/divisions');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_division_detail(): void
    {
        $division = Division::factory()->create([
            'area_id' => $area = Area::factory()->create()->id,
            'code' => 'DIV001',
        ]);
        $user = User::factory()->create(['area_id' => $area]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/divisions/'.$division->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'DIV001')
            ->assertJsonPath('data.area_name', $division->area?->name);
    }

    public function test_authenticated_user_can_create_division(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/divisions', [
                'code' => 'DIV001',
                'name' => 'Divisi A',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Division created successfully')
            ->assertJsonPath('data.code', 'DIV001');

        $this->assertDatabaseHas('divisions', [
            'code' => 'DIV001',
            'name' => 'Divisi A',
            'area_id' => $area->id,
        ]);
    }

    public function test_create_division_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/divisions', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_division(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $division = Division::factory()->create([
            'code' => 'DIV001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/divisions/'.$division->id, [
                'area_id' => $area->id,
                'code' => 'DIV002',
                'name' => 'Divisi B',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Division updated successfully')
            ->assertJsonPath('data.code', 'DIV002');
    }

    public function test_authenticated_user_cannot_update_deleted_division(): void
    {
        $user = User::factory()->create();
        $division = Division::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/divisions/'.$division->id, [
                'area_id' => null,
                'code' => 'DIV002',
                'name' => 'Divisi B',
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Division has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_division_using_status_flag(): void
    {
        $user = User::factory()->create();
        $division = Division::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/divisions/'.$division->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Division deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('divisions', [
            'id' => $division->id,
            'status' => 99,
        ]);
    }

    public function test_delete_division_returns_error_when_division_already_deleted(): void
    {
        $user = User::factory()->create();
        $division = Division::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/divisions/'.$division->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Division has already been deleted.');
    }

    public function test_authenticated_user_can_toggle_division_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $division = Division::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/division_setstatus/'.$division->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Division status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
