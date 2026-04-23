<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Operation;
use App\Models\Part;
use App\Models\PartSerialNumber;
use App\Models\Reason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_parts_with_pagination(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Part::factory()->forArea($area)->count(12)->create();
        $partWithCounts = Part::factory()->forArea($area)->create([
            'code' => 'PRT-COUNT',
        ]);
        $operationOne = Operation::factory()->forArea($area)->create();
        $operationTwo = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $partWithCounts->operations()->sync([$operationOne->id, $operationTwo->id]);
        $partWithCounts->reasons()->sync([$reason->id]);
        PartSerialNumber::factory()->forArea($area)->forPart($partWithCounts)->count(3)->create();
        Part::factory()->forArea($area)->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 13);

        $this->assertCount(10, $response->json('data'));
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $item) => $item['code'] === 'PRT-COUNT'
            && $item['total_operation'] === 2
            && $item['total_reason'] === 1
            && $item['total_serial_number'] === 3));
    }

    public function test_authenticated_user_can_list_part_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Part::factory()->forArea($area)->create(['code' => 'PRT001', 'status' => 1]);
        Part::factory()->forArea($area)->create(['code' => 'PRT002', 'status' => 0]);
        Part::factory()->forArea($area)->create(['code' => 'PRT011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/part_active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_parts_by_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Part::factory()->forArea($area)->count(2)->create();
        Part::factory()->forArea(Area::factory()->create())->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_part_detail(): void
    {
        $areaModel = Area::factory()->create();
        $part = Part::factory()->create([
            'area_id' => $areaModel->id,
            'code' => 'PRT001',
        ]);
        $operation = Operation::factory()->forArea($areaModel)->create(['name' => 'Replace Bearing']);
        $reason = Reason::factory()->forArea($areaModel)->create(['name' => 'Worn Out']);
        $part->operations()->sync([$operation->id]);
        $part->reasons()->sync([$reason->id]);
        PartSerialNumber::factory()->forArea($areaModel)->forPart($part)->count(2)->create();
        $user = User::factory()->create(['area_id' => $areaModel->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/parts/'.$part->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'PRT001')
            ->assertJsonPath('data.total_operation', 1)
            ->assertJsonPath('data.total_reason', 1)
            ->assertJsonPath('data.total_serial_number', 2)
            ->assertJsonPath('data.operation_id.0', (string) $operation->id)
            ->assertJsonPath('data.operation_name.0', 'Replace Bearing')
            ->assertJsonPath('data.reason_id.0', (string) $reason->id)
            ->assertJsonPath('data.reason_name.0', 'Worn Out');
    }

    public function test_authenticated_user_can_create_part(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $operationOne = Operation::factory()->forArea($area)->create(['name' => 'Check Motor']);
        $operationTwo = Operation::factory()->forArea($area)->create(['name' => 'Clean Unit']);
        $reasonOne = Reason::factory()->forArea($area)->create(['name' => 'Broken']);
        $reasonTwo = Reason::factory()->forArea($area)->create(['name' => 'Leaking']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/parts', [
                'code' => 'PRT001',
                'name' => 'Part A',
                'description' => 'Deskripsi part',
                'status' => 1,
                'operation_id' => [$operationOne->id, $operationTwo->id],
                'reason_id' => [$reasonOne->id, $reasonTwo->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Part created successfully')
            ->assertJsonPath('data.code', 'PRT001')
            ->assertJsonPath('data.operation_name.0', 'Check Motor')
            ->assertJsonPath('data.operation_name.1', 'Clean Unit')
            ->assertJsonPath('data.reason_name.0', 'Broken')
            ->assertJsonPath('data.reason_name.1', 'Leaking');

        $this->assertDatabaseHas('parts', [
            'code' => 'PRT001',
            'name' => 'Part A',
            'area_id' => $area->id,
        ]);

        $partId = $response->json('data.id');
        $this->assertDatabaseHas('operation_part', [
            'part_id' => $partId,
            'operation_id' => $operationOne->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'part_id' => $partId,
            'operation_id' => $operationTwo->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'part_id' => $partId,
            'reason_id' => $reasonOne->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'part_id' => $partId,
            'reason_id' => $reasonTwo->id,
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
        $oldOperation = Operation::factory()->create();
        $oldReason = Reason::factory()->create();
        $operationOne = Operation::factory()->forArea($area)->create(['name' => 'Inspect']);
        $operationTwo = Operation::factory()->forArea($area)->create(['name' => 'Replace']);
        $reasonOne = Reason::factory()->forArea($area)->create(['name' => 'Noisy']);
        $reasonTwo = Reason::factory()->forArea($area)->create(['name' => 'Overheat']);
        $part->operations()->sync([$oldOperation->id]);
        $part->reasons()->sync([$oldReason->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/parts/'.$part->id, [
                'area_id' => $area->id,
                'code' => 'PRT002',
                'name' => 'Part B',
                'description' => 'Deskripsi baru',
                'status' => 1,
                'operation_id' => [$operationOne->id, $operationTwo->id],
                'reason_id' => [$reasonOne->id, $reasonTwo->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Part updated successfully')
            ->assertJsonPath('data.code', 'PRT002')
            ->assertJsonPath('data.operation_name.0', 'Inspect')
            ->assertJsonPath('data.operation_name.1', 'Replace')
            ->assertJsonPath('data.reason_name.0', 'Noisy')
            ->assertJsonPath('data.reason_name.1', 'Overheat');

        $this->assertDatabaseMissing('operation_part', [
            'part_id' => $part->id,
            'operation_id' => $oldOperation->id,
        ]);
        $this->assertDatabaseMissing('part_reason', [
            'part_id' => $part->id,
            'reason_id' => $oldReason->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'part_id' => $part->id,
            'operation_id' => $operationOne->id,
        ]);
        $this->assertDatabaseHas('operation_part', [
            'part_id' => $part->id,
            'operation_id' => $operationTwo->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'part_id' => $part->id,
            'reason_id' => $reasonOne->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'part_id' => $part->id,
            'reason_id' => $reasonTwo->id,
        ]);
    }

    public function test_create_part_returns_validation_error_when_relation_is_outside_authenticated_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $operation = Operation::factory()->forArea($otherArea)->create();
        $reason = Reason::factory()->forArea($otherArea)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/parts', [
                'code' => 'PRT001',
                'name' => 'Part A',
                'description' => null,
                'status' => 1,
                'operation_id' => [$operation->id],
                'reason_id' => [$reason->id],
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['operation_id.0', 'reason_id.0'],
            ]);
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

    public function test_authenticated_user_can_toggle_part_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/part_setstatus/'.$part->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Part status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
