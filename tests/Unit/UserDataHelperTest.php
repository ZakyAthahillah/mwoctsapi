<?php

namespace Tests\Unit;

use App\Helpers\UserDataHelper;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class UserDataHelperTest extends TestCase
{
    public function test_transform_returns_expected_user_shape(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $user->id = 10;
        $user->created_at = Carbon::parse('2026-04-15 08:30:00');
        $user->updated_at = Carbon::parse('2026-04-15 09:45:00');

        $payload = UserDataHelper::transform($user);

        $this->assertSame([
            'id' => '10',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'created_at' => '2026-04-15 08:30:00',
            'updated_at' => '2026-04-15 09:45:00',
        ], $payload);
    }
}
