<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_shifts_with_pagination(): void
    {
        $user = User::factory()->create();
        Shift::factory()->count(12)->create();
        Shift::factory()->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/shifts?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_shifts_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Shift::factory()->forArea($area)->count(2)->create();
        Shift::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/shifts?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_shift_detail(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->create([
            'name' => 'Shift Pagi',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/shifts/'.$shift->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Shift Pagi');
    }

    public function test_authenticated_user_can_create_shift(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/shifts', [
                'area_id' => $area->id,
                'name' => 'Shift Malam',
                'time_start' => '20:00',
                'time_finish' => '04:00',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Shift created successfully')
            ->assertJsonPath('data.name', 'Shift Malam');

        $this->assertDatabaseHas('shifts', [
            'area_id' => $area->id,
            'name' => 'Shift Malam',
            'time_start' => '20:00:00',
            'time_finish' => '04:00:00',
            'status' => 1,
        ]);
    }

    public function test_create_shift_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/shifts', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_update_shift(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $shift = Shift::factory()->create([
            'name' => 'Shift Pagi',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/shifts/'.$shift->id, [
                'area_id' => $area->id,
                'name' => 'Shift Sore',
                'time_start' => '14:00',
                'time_finish' => '22:00',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Shift updated successfully')
            ->assertJsonPath('data.name', 'Shift Sore')
            ->assertJsonPath('data.time_start', '14:00:00')
            ->assertJsonPath('data.time_finish', '22:00:00');
    }

    public function test_authenticated_user_cannot_update_deleted_shift(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/shifts/'.$shift->id, [
                'area_id' => null,
                'name' => 'Shift Sore',
                'time_start' => '14:00',
                'time_finish' => '22:00',
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Shift has been deleted and cannot be updated.');
    }

    public function test_authenticated_user_can_delete_shift_using_status_flag(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/shifts/'.$shift->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Shift deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'status' => 99,
        ]);
    }

    public function test_delete_shift_returns_error_when_shift_already_deleted(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/shifts/'.$shift->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Shift has already been deleted.');
    }
}
