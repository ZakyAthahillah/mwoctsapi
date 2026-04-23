<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile(): void
    {
        $area = Area::factory()->create([
            'name' => 'Area Profile',
        ]);
        $user = User::factory()->create([
            'area_id' => $area->id,
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'username' => 'profileuser',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('data.name', 'Profile User')
            ->assertJsonPath('data.email', 'profile@example.com')
            ->assertJsonPath('data.username', 'profileuser')
            ->assertJsonPath('data.area_name', 'Area Profile');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'username' => 'olduser',
            'password' => 'oldpassword',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/profile', [
                'name' => 'Updated Profile',
                'email' => 'updated@example.com',
                'username' => 'updatedprofile',
                'image' => 'images/users/profile.png',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile updated successfully')
            ->assertJsonPath('data.name', 'Updated Profile')
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.username', 'updatedprofile')
            ->assertJsonPath('data.image', 'images/users/profile.png');

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword', $user->password));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Profile',
            'email' => 'updated@example.com',
            'username' => 'updatedprofile',
            'image' => 'images/users/profile.png',
        ]);
    }

    public function test_update_profile_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        User::factory()->create([
            'email' => 'taken@example.com',
            'username' => 'takenuser',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/profile', [
                'name' => '',
                'email' => 'taken@example.com',
                'username' => 'takenuser',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'username', 'password'],
            ]);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');

        $this->postJson('/api/profile', [
            'name' => 'Updated Profile',
            'email' => 'updated@example.com',
            'username' => 'updatedprofile',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }
}
