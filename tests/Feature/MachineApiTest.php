<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MachineApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('images/machines'));

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_machines_with_pagination(): void
    {
        $area = Area::factory()->create([
            'name' => 'Area Produksi',
        ]);
        $user = User::factory()->create(['area_id' => $area->id]);
        Machine::factory()->forArea($area)->count(11)->create();
        Machine::factory()->forArea($area)->create([
            'code' => 'MCH-AREA',
        ]);
        Machine::factory()->forArea($area)->deletedStatus()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machines?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $item) => $item['code'] === 'MCH-AREA' && $item['area_name'] === 'Area Produksi'));
    }

    public function test_authenticated_user_can_list_machine_active_with_status_not_equal_eleven(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Machine::factory()->forArea($area)->create(['code' => 'MCH001', 'status' => 1]);
        Machine::factory()->forArea($area)->create(['code' => 'MCH002', 'status' => 0]);
        Machine::factory()->forArea($area)->create(['code' => 'MCH011', 'status' => 11]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machine_active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_filter_machines_by_area(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Machine::factory()->forArea($area)->count(2)->create();
        Machine::factory()->forArea(Area::factory()->create())->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machines');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_machine_detail(): void
    {
        $area = Area::factory()->create([
            'name' => 'Area Utility',
        ]);
        $user = User::factory()->create(['area_id' => $area->id]);
        $machine = Machine::factory()->create([
            'area_id' => $area->id,
            'code' => 'MCH001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machines/'.$machine->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'MCH001')
            ->assertJsonPath('data.area_name', 'Area Utility');
    }

    public function test_admin_can_create_machine(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->admin()->create(['area_id' => $area->id]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/machines', [
                'code' => 'MCH001',
                'name' => 'Mesin Potong',
                'description' => 'Mesin untuk proses potong',
                'image' => 'machines/front.png',
                'image_side' => 'machines/side.png',
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Machine created successfully')
            ->assertJsonPath('data.code', 'MCH001');

        $machineId = $response->json('data.id');

        $response->assertJsonPath('data.image', 'images/machines/'.$machineId.'/front.png')
            ->assertJsonPath('data.image_side', 'images/machines/'.$machineId.'/side.png');

        $this->assertDatabaseHas('machines', [
            'code' => 'MCH001',
            'name' => 'Mesin Potong',
            'area_id' => $area->id,
            'image' => 'images/machines/'.$machineId.'/front.png',
            'image_side' => 'images/machines/'.$machineId.'/side.png',
        ]);
    }

    public function test_create_machine_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/machines', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['code', 'name', 'status'],
            ]);
    }

    public function test_authenticated_user_can_create_machine(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/machines', [
                'code' => 'MCH001',
                'name' => 'Mesin Potong',
                'description' => null,
                'image' => null,
                'image_side' => null,
                'status' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Machine created successfully');
    }

    public function test_authenticated_user_can_create_machine_with_uploaded_images(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/machines', [
                'code' => 'MCH-UPLOAD',
                'name' => 'Mesin Upload',
                'description' => 'Mesin dengan upload gambar',
                'image' => UploadedFile::fake()->image('front.jpg'),
                'image_side' => UploadedFile::fake()->image('side.jpg'),
                'status' => 1,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'MCH-UPLOAD');

        $machineId = $response->json('data.id');
        $imagePath = $response->json('data.image');
        $imageSidePath = $response->json('data.image_side');

        $this->assertNotNull($imagePath);
        $this->assertNotNull($imageSidePath);
        $this->assertStringContainsString('images/machines/'.$machineId.'/', $imagePath);
        $this->assertStringContainsString('images/machines/'.$machineId.'/', $imageSidePath);
        $this->assertFileExists(public_path((string) $imagePath));
        $this->assertFileExists(public_path((string) $imageSidePath));
    }

    public function test_admin_can_update_machine(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $machine = Machine::factory()->create([
            'code' => 'MCH001',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/machines/'.$machine->id, [
                'area_id' => $area->id,
                'code' => 'MCH002',
                'name' => 'Mesin Press',
                'description' => 'Mesin press update',
                'image' => 'machines/front-2.png',
                'image_side' => 'machines/side-2.png',
                'status' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Machine updated successfully')
            ->assertJsonPath('data.code', 'MCH002');
    }

    public function test_admin_cannot_update_deleted_machine(): void
    {
        $admin = User::factory()->admin()->create();
        $machine = Machine::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/machines/'.$machine->id, [
                'area_id' => null,
                'code' => 'MCH002',
                'name' => 'Mesin Press',
                'description' => 'Mesin press update',
                'image' => null,
                'image_side' => null,
                'status' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Machine has been deleted and cannot be updated.');
    }

    public function test_admin_can_delete_machine_using_status_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $machine = Machine::factory()->active()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/machines/'.$machine->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Machine deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'status' => 99,
        ]);
    }

    public function test_delete_machine_returns_error_when_machine_already_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $machine = Machine::factory()->deletedStatus()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/machines/'.$machine->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Machine has already been deleted.');
    }

    public function test_authenticated_user_can_get_legacy_machine_full_data_array(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create([
            'area_id' => $area->id,
        ]);
        $machine = Machine::factory()->forArea($area)->create([
            'code' => 'MCH-LEGACY',
            'name' => 'Legacy Machine',
            'description' => 'Legacy description',
        ]);
        $otherArea = Area::factory()->create();
        Machine::factory()->forArea($otherArea)->create([
            'code' => 'MCH-OTHER',
            'name' => 'Other Machine',
        ]);
        $part = \App\Models\Part::factory()->forArea($area)->create([
            'code' => 'PRT100',
            'name' => 'Bearing',
        ]);

        DB::table('machine_parts')->insert([
            'machine_id' => $machine->id,
            'part_id' => $part->id,
            'sort_order' => 1,
            'pos_x' => '12',
            'pos_y' => '24',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machine/get-full-data-array?term=Legacy');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'MCH-LEGACY')
            ->assertJsonPath('data.0.parts.0.code', 'PRT100')
            ->assertJsonPath('data.0.parts.0.x', '12');
    }

    public function test_authenticated_user_can_get_legacy_machine_positions(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create([
            'area_id' => $area->id,
        ]);
        $machine = Machine::factory()->forArea($area)->create();
        $position = \App\Models\Position::factory()->forArea($area)->create([
            'name' => 'Front Left',
        ]);

        DB::table('machine_position')->insert([
            'machine_id' => $machine->id,
            'position_id' => $position->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machine/'.$machine->id.'/get-position?selected='.$position->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', (string) $position->id)
            ->assertJsonPath('data.0.text', 'Front Left')
            ->assertJsonPath('data.0.selected', true);
    }

    public function test_authenticated_user_can_get_legacy_machine_parts_for_position(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create([
            'area_id' => $area->id,
        ]);
        $machine = Machine::factory()->forArea($area)->create();
        $position = \App\Models\Position::factory()->forArea($area)->create();
        $part = \App\Models\Part::factory()->forArea($area)->create([
            'name' => 'Motor',
        ]);

        DB::table('machine_parts')->insert([
            'machine_id' => $machine->id,
            'part_id' => $part->id,
            'sort_order' => 1,
            'pos_x' => '10',
            'pos_y' => '20',
        ]);

        DB::table('machine_position_parts')->insert([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'serial_number' => 'SN-001',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machine/'.$machine->id.'/'.$position->id.'/get-part');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', (string) $part->id)
            ->assertJsonPath('data.0.serial_number', 'SN-001')
            ->assertJsonPath('data.0.text', 'Motor (SN-001)');
    }

    public function test_authenticated_user_can_get_legacy_machine_job_detail(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create([
            'area_id' => $area->id,
        ]);
        $machine = Machine::factory()->forArea($area)->active()->create([
            'code' => 'MCH-JOB',
            'name' => 'Machine Job',
            'image' => 'images/machines/1/front.png',
            'image_side' => 'images/machines/1/side.png',
        ]);
        $position = \App\Models\Position::factory()->forArea($area)->create();
        $frontPart = \App\Models\Part::factory()->forArea($area)->create([
            'code' => 'PRT-FR',
            'name' => 'Front Part',
        ]);
        $sidePart = \App\Models\Part::factory()->forArea($area)->create([
            'code' => 'PRT-SD',
            'name' => 'Side Part',
        ]);

        DB::table('machine_parts')->insert([
            'machine_id' => $machine->id,
            'part_id' => $frontPart->id,
            'sort_order' => 1,
            'pos_x' => '11',
            'pos_y' => '22',
        ]);

        DB::table('machine_part_sides')->insert([
            'machine_id' => $machine->id,
            'part_id' => $sidePart->id,
            'sort_order' => 1,
            'pos_x' => '33',
            'pos_y' => '44',
        ]);

        DB::table('machine_position_parts')->insert([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $frontPart->id,
            'serial_number' => 'SN-FRONT',
        ]);

        DB::table('machine_position_parts')->insert([
            'area_id' => $area->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $sidePart->id,
            'serial_number' => 'SN-SIDE',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machine/'.$machine->id.'/'.$position->id.'/get-detail-job');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'MCH-JOB')
            ->assertJsonPath('data.parts.0.serial_number', 'SN-FRONT')
            ->assertJsonPath('data.parts_side.0.serial_number', 'SN-SIDE');
    }

    public function test_authenticated_user_can_activate_completed_machine(): void
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->create([
            'status' => 0,
        ]);

        DB::table('machine_progress')->insert([
            'machine_id' => $machine->id,
            'data' => 1,
            'position' => 1,
            'operation' => 0,
            'reason' => 0,
            'image' => 1,
            'part' => 1,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/machines/'.$machine->id.'/activate');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Machine activation updated successfully')
            ->assertJsonPath('data.status', 1)
            ->assertJsonPath('data.progress', 100);
    }

    public function test_activate_machine_returns_error_when_progress_is_incomplete(): void
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->create([
            'status' => 0,
        ]);

        DB::table('machine_progress')->insert([
            'machine_id' => $machine->id,
            'data' => 1,
            'position' => 0,
            'operation' => 0,
            'reason' => 0,
            'image' => 1,
            'part' => 0,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/machines/'.$machine->id.'/activate');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.request.0', 'Machine progress must be 100 before activation.');
    }

    public function test_authenticated_user_can_toggle_machine_status_between_ninety_nine_and_one(): void
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->deletedStatus()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/machine_setstatus/'.$machine->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Machine status updated successfully')
            ->assertJsonPath('data.status', 1);
    }
}
