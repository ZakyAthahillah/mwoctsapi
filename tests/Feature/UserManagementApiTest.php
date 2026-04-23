<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_with_pagination_and_filters(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'status' => 1,
        ]);
        $area = Area::factory()->create();
        User::factory()->create([
            'area_id' => $area->id,
            'name' => 'Operator One',
            'email' => 'operator@example.com',
            'username' => 'operatorone',
            'status' => 1,
            'is_operator' => true,
            'is_admin' => false,
        ]);
        User::factory()->create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'status' => 99,
            'is_operator' => true,
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users?per_page=10&search=operator&status=1&is_operator=1&is_admin=0&area_id='.$area->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Operator One')
            ->assertJsonPath('data.0.area_name', $area->name)
            ->assertJsonPath('data.0.is_operator', true)
            ->assertJsonPath('data.0.is_admin', false);
    }

    public function test_list_users_returns_validation_error_for_invalid_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users?per_page=101');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['per_page'],
            ]);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_can_update_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();
        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$targetUser->id, [
                'area_id' => $area->id,
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'username' => 'updateduser',
                'image' => 'users/updated.png',
                'status' => 1,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'is_operator' => true,
                'is_admin' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User updated successfully')
            ->assertJsonPath('data.area_name', $area->name)
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.username', 'updateduser')
            ->assertJsonPath('data.is_operator', true)
            ->assertJsonPath('data.is_admin', true);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'email' => 'updated@example.com',
            'username' => 'updateduser',
            'area_id' => $area->id,
            'is_operator' => true,
            'is_admin' => true,
        ]);
    }

    public function test_update_user_returns_validation_error_for_invalid_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$targetUser->id, [
                'area_id' => null,
                'name' => '',
                'email' => 'not-an-email',
                'username' => '',
                'image' => null,
                'status' => 1,
                'is_operator' => false,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'username', 'is_admin'],
            ]);
    }

    public function test_non_admin_cannot_update_user(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$targetUser->id, [
                'area_id' => null,
                'name' => 'Blocked',
                'email' => 'blocked@example.com',
                'username' => 'blockeduser',
                'image' => null,
                'status' => 1,
                'is_operator' => false,
                'is_admin' => false,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/users/'.$targetUser->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User deleted successfully')
            ->assertJsonPath('data.id', (string) $targetUser->id);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account_from_management_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/users/'.$admin->id);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.user.0', 'Admin cannot delete their own account through this endpoint.');
    }

    public function test_delete_user_returns_not_found_when_target_does_not_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/users/999999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found');
    }

    public function test_admin_can_toggle_user_status_between_ninety_nine_and_one(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create([
            'status' => 99,
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/user_setstatus/'.$targetUser->id);

        $response->assertOk()
            ->assertJsonPath('message', 'User status updated successfully')
            ->assertJsonPath('data.status', 1);
    }

    public function test_admin_cannot_deactivate_their_own_account_from_setstatus_endpoint(): void
    {
        $admin = User::factory()->admin()->create([
            'status' => 1,
        ]);
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/user_setstatus/'.$admin->id);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonPath('errors.user.0', 'Admin cannot deactivate their own account through this endpoint.');
    }
}
