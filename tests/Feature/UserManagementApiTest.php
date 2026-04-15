<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$targetUser->id, [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'is_admin' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User updated successfully')
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.is_admin', true);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'email' => 'updated@example.com',
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
                'name' => '',
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'is_admin'],
            ]);
    }

    public function test_non_admin_cannot_update_user(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$targetUser->id, [
                'name' => 'Blocked',
                'email' => 'blocked@example.com',
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
}
