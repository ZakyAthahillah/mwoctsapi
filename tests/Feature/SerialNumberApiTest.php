<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\Part;
use App\Models\PartSerialNumber;
use App\Models\Position;
use App\Models\SerialNumber;
use App\Models\SerialNumberLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialNumberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_serial_numbers_with_pagination(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        SerialNumber::factory()->count(12)->create([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/serial-numbers?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_authenticated_user_can_filter_serial_numbers_by_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();

        SerialNumber::factory()->create([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'part_serial_number_id' => PartSerialNumber::factory()->forArea($area)->forPart($part)->create()->id,
        ]);

        SerialNumber::factory()->count(2)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/serial-numbers');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_authenticated_user_can_view_serial_number_detail(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $serialNumber = SerialNumber::factory()->create([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/serial-numbers/'.$serialNumber->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', (string) $serialNumber->id)
            ->assertJsonPath('data.area_name', $serialNumber->area?->name)
            ->assertJsonPath('data.part_serial_number_name', $serialNumber->partSerialNumber?->serial_number);
    }

    public function test_authenticated_user_can_create_serial_number(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/serial-numbers', [
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Serial number created successfully')
            ->assertJsonPath('data.part_serial_number_id', (string) $partSerialNumber->id);

        $this->assertDatabaseHas('serial_numbers', [
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
        ]);

        $this->assertDatabaseHas('serial_number_logs', [
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
            'updatedBy' => $user->id,
            'action' => 1,
        ]);
    }

    public function test_create_serial_number_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/serial-numbers', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['machine_id', 'position_id', 'part_id', 'part_serial_number_id'],
            ]);
    }

    public function test_create_serial_number_returns_error_when_serial_number_is_used_in_same_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machineOne = Machine::factory()->forArea($area)->create();
        $machineTwo = Machine::factory()->forArea($area)->create();
        $positionOne = Position::factory()->forArea($area)->create();
        $positionTwo = Position::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();

        SerialNumber::factory()->create([
            'area_id' => $area->id,
            'machine_id' => $machineOne->id,
            'position_id' => $positionOne->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/serial-numbers', [
                'machine_id' => $machineTwo->id,
                'position_id' => $positionTwo->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Serial number is already used in another machine or position within the selected area.');
    }

    public function test_authenticated_user_can_update_serial_number(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $newPartSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $serialNumber = SerialNumber::factory()->create([
            'area_id' => $area->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/serial-numbers/'.$serialNumber->id, [
                'part_serial_number_id' => $newPartSerialNumber->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Serial number updated successfully')
            ->assertJsonPath('data.part_serial_number_id', (string) $newPartSerialNumber->id);

        $this->assertDatabaseHas('serial_number_logs', [
            'id' => 1,
            'part_serial_number_id' => $newPartSerialNumber->id,
            'action' => 2,
        ]);
    }

    public function test_update_serial_number_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $serialNumber = SerialNumber::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/serial-numbers/'.$serialNumber->id, []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['part_serial_number_id'],
            ]);
    }

    public function test_authenticated_user_can_get_first_serial_number_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();

        SerialNumberLog::create([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
            'updatedBy' => $user->id,
            'updatedDate' => now(),
            'action' => 1,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/serial-numbers/first/'.$partSerialNumber->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.part_serial_number_id', (string) $partSerialNumber->id)
            ->assertJsonPath('data.part_serial_number_name', $partSerialNumber->serial_number)
            ->assertJsonPath('data.area_name', $area->name)
            ->assertJsonPath('data.first_assignment.machine_id', (string) $machine->id)
            ->assertJsonPath('data.first_assignment.updated_by_name', $user->name);
    }

    public function test_authenticated_user_can_update_first_serial_number_assignment(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $part = Part::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/serial-numbers/first/'.$partSerialNumber->id, [
                'machine_id' => $machine->id,
                'position_id' => $position->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Initial serial number assignment updated successfully')
            ->assertJsonPath('data.part_serial_number_id', (string) $partSerialNumber->id);

        $this->assertDatabaseHas('serial_numbers', [
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'part_serial_number_id' => $partSerialNumber->id,
        ]);
    }

    public function test_update_first_returns_error_when_part_serial_number_is_inactive(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->inactive()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/serial-numbers/first/'.$partSerialNumber->id, [
                'machine_id' => $machine->id,
                'position_id' => $position->id,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Part serial number is not active and cannot be assigned.');
    }
}
