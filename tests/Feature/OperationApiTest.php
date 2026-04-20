<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Operation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_operations_with_pagination(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Operation::factory()->forArea($area)->count(12)->create();
        Operation::factory()->forArea($area)->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operations?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_list_operation_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Operation::factory()->forArea($area)->create(['code' => 'OPR001', 'status' => 1]);
        Operation::factory()->forArea($area)->create(['code' => 'OPR002', 'status' => 0]);
        Operation::factory()->forArea($area)->create(['code' => 'OPR011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operation_active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_operations_by_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Operation::factory()->forArea($area)->count(2)->create();
        Operation::factory()->forArea(Area::factory()->create())->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operations');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_operation_detail(): void
    {
        $operation = Operation::factory()->create([
            'area_id' => $area = Area::factory()->create()->id,
            'code' => 'OPR001',
        ]);
        $user = User::factory()->create(['area_id' => $area]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operations/'.$operation->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'OPR001');
    }

    public function test_authenticated_user_can_create_operation(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/operations', [
                'code' => 'OPR001',
                'name' => 'Pekerjaan A',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operation created successfully')
            ->assertJsonPath('data.code', 'OPR001');

        $this->assertDatabaseHas('operations', [
            'code' => 'OPR001',
            'name' => 'Pekerjaan A',
            'area_id' => $area->id,
        ]);
    }

    public function test_create_operation_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/operations', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_operation(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $operation = Operation::factory()->create([
            'code' => 'OPR001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/operations/'.$operation->id, [
                'area_id' => $area->id,
                'code' => 'OPR002',
                'name' => 'Pekerjaan B',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operation updated successfully')
            ->assertJsonPath('data.code', 'OPR002');
    }

    public function test_authenticated_user_cannot_update_deleted_operation(): void
    {
        $user = User::factory()->create();
        $operation = Operation::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/operations/'.$operation->id, [
                'area_id' => null,
                'code' => 'OPR002',
                'name' => 'Pekerjaan B',
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Operation has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_operation_using_status_flag(): void
    {
        $user = User::factory()->create();
        $operation = Operation::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/operations/'.$operation->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operation deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('operations', [
            'id' => $operation->id,
            'status' => 99,
        ]);
    }

    public function test_delete_operation_returns_error_when_operation_already_deleted(): void
    {
        $user = User::factory()->create();
        $operation = Operation::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/operations/'.$operation->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Operation has already been deleted.');
    }

    public function test_authenticated_user_can_toggle_operation_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $operation = Operation::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/operation_setstatus/'.$operation->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Operation status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
