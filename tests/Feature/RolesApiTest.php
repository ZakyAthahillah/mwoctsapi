<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('guard_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
            });
        }
    }

    public function test_admin_can_manage_roles(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/roles', [
                'name' => 'maintenance-admin',
                'display_name' => 'Maintenance Admin',
                'guard_name' => 'api',
                'area_id' => $area->id,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Role created successfully')
            ->assertJsonPath('data.name', $area->id.'_maintenance-admin')
            ->assertJsonPath('data.display_name', 'Maintenance Admin');

        $roleId = DB::table('roles')->value('id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles?per_page=10&search=maintenance')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (string) $roleId);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles/'.$roleId)
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Maintenance Admin');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/roles/'.$roleId, [
                'display_name' => 'Maintenance Supervisor',
                'guard_name' => 'api',
                'area_id' => $area->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Role updated successfully')
            ->assertJsonPath('data.display_name', 'Maintenance Supervisor');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/roles/'.$roleId)
            ->assertOk()
            ->assertJsonPath('message', 'Role deleted successfully')
            ->assertJsonPath('data.display_name', 'Maintenance Supervisor');
    }

    public function test_admin_can_view_and_update_role_permissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);
        $roleId = $this->insertRole(['name' => 'maintenance-admin']);
        $permissionOne = $this->insertPermission('roles-index');
        $permissionTwo = $this->insertPermission('roles-update');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles/'.$roleId.'/permissions')
            ->assertOk()
            ->assertJsonPath('data.role.id', (string) $roleId)
            ->assertJsonPath('data.permissions.0.name', 'roles-index')
            ->assertJsonPath('data.permissions.0.selected', false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/roles/'.$roleId.'/permissions', [
                'permission_ids' => [$permissionOne, $permissionTwo],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Role permissions updated successfully')
            ->assertJsonPath('data.permissions.0.name', 'roles-index')
            ->assertJsonPath('data.permissions.1.name', 'roles-update');

        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionOne,
        ]);
    }

    public function test_area_admin_only_accesses_roles_in_their_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $admin = User::factory()->create(['is_admin' => true, 'area_id' => $area->id]);
        $token = auth('api')->login($admin);
        $visibleRole = $this->insertRole(['area_id' => $area->id, 'name' => $area->id.'_operator']);
        $hiddenRole = $this->insertRole(['area_id' => $otherArea->id, 'name' => $otherArea->id.'_operator']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (string) $visibleRole);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles/'.$hiddenRole)
            ->assertStatus(404)
            ->assertJsonPath('message', 'Resource not found');
    }

    public function test_role_create_returns_validation_error_when_payload_is_invalid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/roles', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'display_name'],
            ]);
    }

    public function test_role_create_returns_validation_error_when_name_is_duplicate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);
        $this->insertRole(['name' => 'maintenance-admin']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/roles', [
                'name' => 'maintenance-admin',
                'display_name' => 'Maintenance Admin',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.name.0', 'The name has already been taken.');
    }

    public function test_non_admin_cannot_access_roles_endpoint(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/roles');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_roles_require_authentication(): void
    {
        $this->getJson('/api/roles')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function insertRole(array $overrides = []): int
    {
        return DB::table('roles')->insertGetId([
            'name' => 'role-a',
            'display_name' => 'Role A',
            'guard_name' => 'api',
            'area_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }

    private function insertPermission(string $name): int
    {
        return DB::table('permissions')->insertGetId([
            'name' => $name,
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
