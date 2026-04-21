<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\Part;
use App\Models\Reason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReasonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_reasons_with_pagination(): void
    {
        $area = Area::factory()->create();
        $division = Division::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Reason::factory()->forArea($area)->count(12)->create(['division_id' => $division->id]);
        Reason::factory()->forArea($area)->deletedStatus()->create(['division_id' => $division->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reasons?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_list_reason_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $division = Division::factory()->forArea($area)->create();
        Reason::factory()->forArea($area)->create(['division_id' => $division->id, 'code' => 'RSN001', 'status' => 1]);
        Reason::factory()->forArea($area)->create(['division_id' => $division->id, 'code' => 'RSN002', 'status' => 0]);
        Reason::factory()->forArea($area)->create(['division_id' => $division->id, 'code' => 'RSN011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reason_active?division_id='.$division->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_reasons_by_area(): void
    {
        $area = Area::factory()->create();
        $division = Division::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $reasons = Reason::factory()->forArea($area)->count(2)->create(['division_id' => $division->id]);
        $reasons->each(fn (Reason $reason) => $reason->divisions()->sync([$division->id]));
        Reason::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reasons');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_reason_detail(): void
    {
        $area = Area::factory()->create([
            'name' => 'Area Alasan',
        ]);
        $user = User::factory()->create(['area_id' => $area->id]);
        $divisionOne = Division::factory()->forArea($area)->create([
            'code' => 'DIV777',
            'name' => 'Divisi Alasan',
        ]);
        $divisionTwo = Division::factory()->forArea($area)->create([
            'name' => 'Divisi Tambahan',
        ]);
        $part = Part::factory()->forArea($area)->create([
            'name' => 'HC Blower',
        ]);
        $reason = Reason::factory()->create([
            'area_id' => $area->id,
            'code' => 'RSN001',
            'division_id' => $divisionOne->id,
        ]);
        $reason->divisions()->sync([$divisionOne->id, $divisionTwo->id]);
        $reason->parts()->sync([$part->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reasons/'.$reason->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'RSN001')
            ->assertJsonPath('data.area_name', 'Area Alasan')
            ->assertJsonPath('data.division_id.0', (string) $divisionOne->id)
            ->assertJsonPath('data.division_id.1', (string) $divisionTwo->id)
            ->assertJsonPath('data.division_name.0', 'Divisi Alasan')
            ->assertJsonPath('data.division_name.1', 'Divisi Tambahan')
            ->assertJsonPath('data.part_id.0', (string) $part->id)
            ->assertJsonPath('data.part_name.0', 'HC Blower');
    }

    public function test_authenticated_user_can_create_reason(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $divisionOne = Division::factory()->forArea($area)->create([
            'code' => 'DIV001',
            'name' => 'Divisi A',
        ]);
        $divisionTwo = Division::factory()->forArea($area)->create([
            'name' => 'Divisi B',
        ]);
        $partOne = Part::factory()->forArea($area)->create([
            'name' => 'HC Blower',
        ]);
        $partTwo = Part::factory()->forArea($area)->create([
            'name' => 'HC Exchanger',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reasons', [
                'code' => 'RSN001',
                'name' => 'Alasan A',
                'division_id' => [$divisionOne->id, $divisionTwo->id],
                'part_id' => [$partOne->id, $partTwo->id],
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reason created successfully')
            ->assertJsonPath('data.code', 'RSN001')
            ->assertJsonPath('data.division_id.0', (string) $divisionOne->id)
            ->assertJsonPath('data.division_id.1', (string) $divisionTwo->id)
            ->assertJsonPath('data.part_id.0', (string) $partOne->id)
            ->assertJsonPath('data.part_id.1', (string) $partTwo->id);

        $this->assertDatabaseHas('reasons', [
            'code' => 'RSN001',
            'name' => 'Alasan A',
            'area_id' => $area->id,
            'division_id' => $divisionOne->id,
        ]);

        $reasonId = (int) $response->json('data.id');

        $this->assertDatabaseHas('division_reason', [
            'reason_id' => $reasonId,
            'division_id' => $divisionOne->id,
        ]);
        $this->assertDatabaseHas('division_reason', [
            'reason_id' => $reasonId,
            'division_id' => $divisionTwo->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'reason_id' => $reasonId,
            'part_id' => $partOne->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'reason_id' => $reasonId,
            'part_id' => $partTwo->id,
        ]);
    }

    public function test_create_reason_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reasons', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_reason(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $oldDivision = Division::factory()->create();
        $oldPart = Part::factory()->create();
        $divisionOne = Division::factory()->forArea($area)->create([
            'code' => 'DIV001',
            'name' => 'Divisi A',
        ]);
        $divisionTwo = Division::factory()->forArea($area)->create([
            'name' => 'Divisi B',
        ]);
        $partOne = Part::factory()->forArea($area)->create([
            'name' => 'Pump',
        ]);
        $partTwo = Part::factory()->forArea($area)->create([
            'name' => 'Valve',
        ]);
        $reason = Reason::factory()->create([
            'code' => 'RSN001',
            'division_id' => $oldDivision->id,
        ]);
        $reason->divisions()->sync([$oldDivision->id]);
        $reason->parts()->sync([$oldPart->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/reasons/'.$reason->id, [
                'area_id' => $area->id,
                'code' => 'RSN002',
                'name' => 'Alasan B',
                'division_id' => [$divisionOne->id, $divisionTwo->id],
                'part_id' => [$partOne->id, $partTwo->id],
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reason updated successfully')
            ->assertJsonPath('data.code', 'RSN002')
            ->assertJsonPath('data.division_name.0', 'Divisi A')
            ->assertJsonPath('data.division_name.1', 'Divisi B')
            ->assertJsonPath('data.part_name.0', 'Pump')
            ->assertJsonPath('data.part_name.1', 'Valve');

        $this->assertDatabaseMissing('division_reason', [
            'reason_id' => $reason->id,
            'division_id' => $oldDivision->id,
        ]);
        $this->assertDatabaseMissing('part_reason', [
            'reason_id' => $reason->id,
            'part_id' => $oldPart->id,
        ]);
        $this->assertDatabaseHas('reasons', [
            'id' => $reason->id,
            'division_id' => $divisionOne->id,
        ]);
        $this->assertDatabaseHas('division_reason', [
            'reason_id' => $reason->id,
            'division_id' => $divisionOne->id,
        ]);
        $this->assertDatabaseHas('division_reason', [
            'reason_id' => $reason->id,
            'division_id' => $divisionTwo->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'reason_id' => $reason->id,
            'part_id' => $partOne->id,
        ]);
        $this->assertDatabaseHas('part_reason', [
            'reason_id' => $reason->id,
            'part_id' => $partTwo->id,
        ]);
    }

    public function test_create_reason_returns_validation_error_when_relation_is_outside_authenticated_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $foreignDivision = Division::factory()->forArea($otherArea)->create();
        $foreignPart = Part::factory()->forArea($otherArea)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reasons', [
                'code' => 'RSN-REL',
                'name' => 'Alasan Relation',
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

    public function test_authenticated_user_cannot_update_deleted_reason(): void
    {
        $user = User::factory()->create();
        $reason = Reason::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/reasons/'.$reason->id, [
                'area_id' => null,
                'code' => 'RSN002',
                'name' => 'Alasan B',
                'division_id' => null,
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Reason has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_reason_using_status_flag(): void
    {
        $user = User::factory()->create();
        $reason = Reason::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/reasons/'.$reason->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reason deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('reasons', [
            'id' => $reason->id,
            'status' => 99,
        ]);
    }

    public function test_delete_reason_returns_error_when_reason_already_deleted(): void
    {
        $user = User::factory()->create();
        $reason = Reason::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/reasons/'.$reason->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Reason has already been deleted.');
    }

    public function test_authenticated_user_can_toggle_reason_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $reason = Reason::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/reason_setstatus/'.$reason->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Reason status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
