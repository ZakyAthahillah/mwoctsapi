<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Group;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TechnicianApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_technicians_with_pagination(): void
    {
        $area = Area::factory()->create();
        $division = \App\Models\Division::factory()->forArea($area)->create();
        $group = Group::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Technician::factory()->forArea($area)->count(12)->create(['division_id' => $division->id, 'group_id' => $group->id]);
        Technician::factory()->forArea($area)->deletedStatus()->create(['division_id' => $division->id, 'group_id' => $group->id]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/technicians?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_list_technician_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $division = \App\Models\Division::factory()->forArea($area)->create();
        $group = \App\Models\Group::factory()->forArea($area)->create();
        Technician::factory()->forArea($area)->create(['division_id' => $division->id, 'group_id' => $group->id, 'code' => 'TCN001', 'status' => 1]);
        Technician::factory()->forArea($area)->create(['division_id' => $division->id, 'group_id' => $group->id, 'code' => 'TCN002', 'status' => 0]);
        Technician::factory()->forArea($area)->create(['division_id' => $division->id, 'group_id' => $group->id, 'code' => 'TCN011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/technician_active?division_id='.$division->id.'&group_id='.$group->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_technicians_by_area(): void
    {
        $area = Area::factory()->create();
        $division = \App\Models\Division::factory()->forArea($area)->create();
        $group = Group::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Technician::factory()->forArea($area)->count(2)->create(['division_id' => $division->id, 'group_id' => $group->id]);
        Technician::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/technicians');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_technician_detail(): void
    {
        $area = Area::factory()->create([
            'name' => 'Area Teknisi',
        ]);
        $user = User::factory()->create(['area_id' => $area->id]);
        $group = Group::factory()->forArea($area)->create([
            'name' => 'Group Teknisi',
        ]);
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV999',
            'name' => 'Divisi Teknisi',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $technician = Technician::factory()->create([
            'area_id' => $area->id,
            'code' => 'TCN001',
            'division_id' => $divisionId,
            'group_id' => $group->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/technicians/'.$technician->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'TCN001')
            ->assertJsonPath('data.area_name', 'Area Teknisi')
            ->assertJsonPath('data.division_name', 'Divisi Teknisi')
            ->assertJsonPath('data.group_name', 'Group Teknisi');
    }

    public function test_admin_can_create_technician(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->admin()->create(['area_id' => $area->id]);
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV001',
            'name' => 'Divisi A',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/technicians', [
                'code' => 'TCN001',
                'name' => 'Teknisi A',
                'division_id' => $divisionId,
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Technician created successfully')
            ->assertJsonPath('data.code', 'TCN001');

        $this->assertDatabaseHas('technicians', [
            'code' => 'TCN001',
            'name' => 'Teknisi A',
            'area_id' => $area->id,
            'division_id' => $divisionId,
        ]);
    }

    public function test_create_technician_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/technicians', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_create_technician(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/technicians', [
                'code' => 'TCN001',
                'name' => 'Teknisi A',
                'division_id' => null,
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Technician created successfully');
    }

    public function test_admin_can_update_technician(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $divisionId = DB::table('divisions')->insertGetId([
            'area_id' => $area->id,
            'code' => 'DIV001',
            'name' => 'Divisi A',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $technician = Technician::factory()->create([
            'code' => 'TCN001',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/technicians/'.$technician->id, [
                'area_id' => $area->id,
                'code' => 'TCN002',
                'name' => 'Teknisi B',
                'division_id' => $divisionId,
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Technician updated successfully')
            ->assertJsonPath('data.code', 'TCN002');
    }

    public function test_admin_cannot_update_deleted_technician(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = Technician::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/technicians/'.$technician->id, [
                'area_id' => null,
                'code' => 'TCN002',
                'name' => 'Teknisi B',
                'division_id' => null,
                'status' => 1,
                'group_id' => null,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Technician has been deleted and cannot be updated.');
    }

    public function test_admin_can_delete_technician_using_status_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = Technician::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/technicians/'.$technician->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Technician deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'status' => 99,
        ]);
    }

    public function test_delete_technician_returns_error_when_technician_already_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = Technician::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/technicians/'.$technician->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Technician has already been deleted.');
    }

    public function test_authenticated_user_can_toggle_technician_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $technician = Technician::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/technician_setstatus/'.$technician->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Technician status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
