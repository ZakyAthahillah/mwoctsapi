<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_and_user_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Admin',
            'email' => 'john@example.com',
            'username' => 'johnadmin',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User registered successfully')
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('data.user.username', 'johnadmin')
            ->assertJsonPath('data.user.status', 1)
            ->assertJsonPath('data.user.is_operator', false)
            ->assertJsonPath('data.user.is_admin', false)
            ->assertJsonPath('data.authorization.type', 'bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johnadmin',
            'is_admin' => false,
        ]);
    }

    public function test_register_returns_validation_error_when_payload_is_invalid(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'username', 'password'],
            ]);
    }

    public function test_login_returns_token_when_credentials_are_valid(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.authorization.type', 'bearer');
    }

    public function test_login_returns_unauthorized_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials')
            ->assertJsonPath('errors.auth.0', 'Email or password is incorrect.');
    }

    public function test_logout_invalidates_the_token(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'secret123',
        ]);

        $token = auth('api')->login($admin);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logoutResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout successful');

        $retryResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $retryResponse->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_refresh_returns_a_new_token(): void
    {
        $user = User::factory()->create([
            'email' => 'refresh@example.com',
            'password' => 'secret123',
        ]);

        $oldToken = auth('api')->login($user);

        $refreshResponse = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/refresh');

        $refreshResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token refreshed successfully')
            ->assertJsonPath('data.user.email', 'refresh@example.com')
            ->assertJsonPath('data.authorization.type', 'bearer');

        $newToken = $refreshResponse->json('data.authorization.token');

        $this->assertNotSame($oldToken, $newToken);

        $newTokenLogoutResponse = $this->withHeader('Authorization', 'Bearer '.$newToken)
            ->postJson('/api/logout');

        $newTokenLogoutResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout successful');
    }

    public function test_refresh_requires_a_token(): void
    {
        $response = $this->postJson('/api/refresh');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_authenticated_user_can_get_profile_for_navbar(): void
    {
        $area = Area::factory()->create([
            'name' => 'Jakarta Barat',
            'object_name' => 'Site',
        ]);
        $user = User::factory()->create([
            'area_id' => $area->id,
            'name' => 'Navbar User',
            'username' => 'navbaruser',
            'email' => 'navbar@example.com',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('data.name', 'Navbar User')
            ->assertJsonPath('data.username', 'navbaruser')
            ->assertJsonPath('data.area.id', (string) $area->id)
            ->assertJsonPath('data.area.name', 'Jakarta Barat')
            ->assertJsonPath('data.area.object_name', 'Site');
    }

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }
}
