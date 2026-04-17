<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermissionsApiTest extends TestCase
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
    }

    public function test_admin_can_manage_permissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permissions', [
                'name' => 'jobs-index',
                'guard_name' => 'api',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'jobs-index');

        $permissionId = DB::table('permissions')->value('id');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permissions?per_page=10');

        $listResponse->assertOk()
            ->assertJsonPath('meta.total', 1);

        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/permissions/'.$permissionId, [
                'name' => 'jobs-manage',
                'guard_name' => 'api',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'jobs-manage');

        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/permissions/'.$permissionId);

        $deleteResponse->assertOk()
            ->assertJsonPath('data.name', 'jobs-manage');
    }

    public function test_non_admin_cannot_access_permissions_endpoint(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permissions');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_permission_create_returns_validation_error_when_name_is_duplicate(): void
    {
        DB::table('permissions')->insert([
            'name' => 'jobs-index',
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permissions', [
                'name' => 'jobs-index',
                'guard_name' => 'api',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['name'],
            ]);
    }
}
