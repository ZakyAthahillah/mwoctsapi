<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Reason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReasonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_reasons_with_pagination(): void
    {
        $user = User::factory()->create();
        Reason::factory()->count(12)->create();
        Reason::factory()->deletedStatus()->create();

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

    public function test_authenticated_user_can_filter_reasons_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Reason::factory()->forArea($area)->count(2)->create();
        Reason::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reasons?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_reason_detail(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create([
            'name' => 'Area Alasan',
        ]);
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV777',
            'name' => 'Divisi Alasan',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reason = Reason::factory()->create([
            'area_id' => $area->id,
            'code' => 'RSN001',
            'division_id' => $divisionId,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reasons/'.$reason->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'RSN001')
            ->assertJsonPath('data.area_name', 'Area Alasan')
            ->assertJsonPath('data.division_name', 'Divisi Alasan');
    }

    public function test_authenticated_user_can_create_reason(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV001',
            'name' => 'Divisi A',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reasons', [
                'area_id' => $area->id,
                'code' => 'RSN001',
                'name' => 'Alasan A',
                'division_id' => $divisionId,
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reason created successfully')
            ->assertJsonPath('data.code', 'RSN001');

        $this->assertDatabaseHas('reasons', [
            'code' => 'RSN001',
            'name' => 'Alasan A',
            'area_id' => $area->id,
            'division_id' => $divisionId,
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
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV001',
            'name' => 'Divisi A',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reason = Reason::factory()->create([
            'code' => 'RSN001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/reasons/'.$reason->id, [
                'area_id' => $area->id,
                'code' => 'RSN002',
                'name' => 'Alasan B',
                'division_id' => $divisionId,
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reason updated successfully')
            ->assertJsonPath('data.code', 'RSN002');
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
}
