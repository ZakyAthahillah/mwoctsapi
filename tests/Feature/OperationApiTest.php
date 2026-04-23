<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\Operation;
use App\Models\Part;
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
        Operation::factory()->forArea($area)->count(11)->create();
        $division = Division::factory()->forArea($area)->create([
            'name' => 'Mechanical',
        ]);
        $part = Part::factory()->forArea($area)->create([
            'name' => 'HC Blower',
        ]);
        $operationWithRelations = Operation::factory()->forArea($area)->create([
            'code' => 'OPR-REL',
        ]);
        $operationWithRelations->divisions()->sync([$division->id]);
        $operationWithRelations->parts()->sync([$part->id]);
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
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $item) => $item['code'] === 'OPR-REL'
            && $item['total_division'] === 1
            && $item['total_part'] === 1));
    }

    public function test_authenticated_user_can_list_operation_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $division = Division::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create(['code' => 'OPR001', 'status' => 1]);
        $operation->divisions()->sync([$division->id]);
        $operation->parts()->sync([$part->id]);
        Operation::factory()->forArea($area)->create(['code' => 'OPR002', 'status' => 0]);
        Operation::factory()->forArea($area)->create(['code' => 'OPR011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operation_active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->assertTrue(collect($response->json('data'))->contains(fn (array $item) => $item['code'] === 'OPR001'
            && $item['total_division'] === 1
            && $item['total_part'] === 1));
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
        $area = Area::factory()->create();
        $operation = Operation::factory()->create([
            'area_id' => $area->id,
            'code' => 'OPR001',
        ]);
        $division = Division::factory()->forArea($area)->create([
            'name' => 'Mechanical',
        ]);
        $part = Part::factory()->forArea($area)->create([
            'name' => 'HC Blower',
        ]);
        $operation->divisions()->sync([$division->id]);
        $operation->parts()->sync([$part->id]);
        $user = User::factory()->create(['area_id' => $area->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/operations/'.$operation->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'OPR001')
            ->assertJsonPath('data.division_id.0', (string) $division->id)
            ->assertJsonPath('data.division_name.0', 'Mechanical')
            ->assertJsonPath('data.part_id.0', (string) $part->id)
            ->assertJsonPath('data.part_name.0', 'HC Blower');
    }

    public function test_authenticated_user_can_create_operation(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $divisionOne = Division::factory()->forArea($area)->create([
            'name' => 'Mechanical',
        ]);
        $divisionTwo = Division::factory()->forArea($area)->create([
            'name' => 'Electrical',
        ]);
        $partOne = Part::factory()->forArea($area)->create([
            'name' => 'HC Blower',
        ]);
        $partTwo = Part::factory()->forArea($area)->create([
            'name' => 'HC Exchanger',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/operations', [
                'code' => 'OPR001',
                'name' => 'Pekerjaan A',
                'division_id' => [$divisionOne->id, $divisionTwo->id],
                'part_id' => [$partOne->id, $partTwo->id],
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operation created successfully')
            ->assertJsonPath('data.code', 'OPR001')
            ->assertJsonPath('data.division_id.0', (string) $divisionOne->id)
            ->assertJsonPath('data.division_id.1', (string) $divisionTwo->id)
            ->assertJsonPath('data.part_id.0', (string) $partOne->id)
            ->assertJsonPath('data.part_id.1', (string) $partTwo->id);

        $this->assertDatabaseHas('operations', [
            'code' => 'OPR001',
            'name' => 'Pekerjaan A',
            'area_id' => $area->id,
        ]);

        $operationId = (int) $response->json('data.id');

        $this->assertDatabaseHas('division_operation', [
            'operation_id' => $operationId,
            'division_id' => $divisionOne->id,
        ]);
        $this->assertDatabaseHas('division_operation', [
            'operation_id' => $operationId,
            'division_id' => $divisionTwo->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'operation_id' => $operationId,
            'part_id' => $partOne->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'operation_id' => $operationId,
            'part_id' => $partTwo->id,
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
        $oldDivision = Division::factory()->create();
        $oldPart = Part::factory()->create();
        $newDivisionOne = Division::factory()->forArea($area)->create([
            'name' => 'Production',
        ]);
        $newDivisionTwo = Division::factory()->forArea($area)->create([
            'name' => 'Maintenance',
        ]);
        $newPartOne = Part::factory()->forArea($area)->create([
            'name' => 'Pump',
        ]);
        $newPartTwo = Part::factory()->forArea($area)->create([
            'name' => 'Valve',
        ]);
        $operation->divisions()->sync([$oldDivision->id]);
        $operation->parts()->sync([$oldPart->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/operations/'.$operation->id, [
                'area_id' => $area->id,
                'code' => 'OPR002',
                'name' => 'Pekerjaan B',
                'division_id' => [$newDivisionOne->id, $newDivisionTwo->id],
                'part_id' => [$newPartOne->id, $newPartTwo->id],
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operation updated successfully')
            ->assertJsonPath('data.code', 'OPR002')
            ->assertJsonPath('data.division_name.0', 'Production')
            ->assertJsonPath('data.division_name.1', 'Maintenance')
            ->assertJsonPath('data.part_name.0', 'Pump')
            ->assertJsonPath('data.part_name.1', 'Valve');

        $this->assertDatabaseMissing('division_operation', [
            'operation_id' => $operation->id,
            'division_id' => $oldDivision->id,
        ]);
        $this->assertDatabaseMissing('operation_part', [
            'operation_id' => $operation->id,
            'part_id' => $oldPart->id,
        ]);
        $this->assertDatabaseHas('division_operation', [
            'operation_id' => $operation->id,
            'division_id' => $newDivisionOne->id,
        ]);
        $this->assertDatabaseHas('division_operation', [
            'operation_id' => $operation->id,
            'division_id' => $newDivisionTwo->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'operation_id' => $operation->id,
            'part_id' => $newPartOne->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'operation_id' => $operation->id,
            'part_id' => $newPartTwo->id,
        ]);
    }

    public function test_create_operation_returns_validation_error_when_relation_is_outside_authenticated_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $foreignDivision = Division::factory()->forArea($otherArea)->create();
        $foreignPart = Part::factory()->forArea($otherArea)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/operations', [
                'code' => 'OPR-REL',
                'name' => 'Pekerjaan Relation',
                'division_id' => [$foreignDivision->id],
                'part_id' => [$foreignPart->id],
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['division_id.0', 'part_id.0'],
            ]);
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
