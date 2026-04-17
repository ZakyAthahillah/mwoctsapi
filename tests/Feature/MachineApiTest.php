<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $user = User::factory()->create();
        $area = Area::factory()->create([
            'name' => 'Area Produksi',
        ]);
        Machine::factory()->count(11)->create();
        Machine::factory()->forArea($area)->create([
            'code' => 'MCH-AREA',
        ]);
        Machine::factory()->deletedStatus()->create();

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

    public function test_authenticated_user_can_filter_machines_by_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        Machine::factory()->forArea($area)->count(2)->create();
        Machine::factory()->count(3)->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/machines?area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_authenticated_user_can_view_machine_detail(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create([
            'name' => 'Area Utility',
        ]);
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
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/machines', [
                'area_id' => $area->id,
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
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/machines', [
                'area_id' => null,
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
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/machines', [
                'area_id' => $area->id,
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
}
