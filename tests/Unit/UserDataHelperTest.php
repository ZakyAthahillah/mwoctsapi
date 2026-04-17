<?php

namespace Tests\Unit;

use App\Helpers\UserDataHelper;
use App\Models\Area;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class UserDataHelperTest extends TestCase
{
    public function test_transform_returns_expected_user_shape(): void
    {
        $user = new User;
        $user->forceFill([
            'area_id' => 3,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'adminuser',
            'image' => 'profiles/admin.png',
            'status' => 1,
            'password' => 'secret123',
            'is_operator' => true,
            'is_admin' => true,
        ]);

        $user->id = 10;
        $user->created_at = Carbon::parse('2026-04-15 08:30:00');
        $user->updated_at = Carbon::parse('2026-04-15 09:45:00');
        $user->setRelation('area', tap(new Area, function (Area $area): void {
            $area->id = 3;
            $area->name = 'Area Testing';
        }));

        $payload = UserDataHelper::transform($user);

        $this->assertSame([
            'id' => '10',
            'area_id' => '3',
            'area_name' => 'Area Testing',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'adminuser',
            'image' => 'profiles/admin.png',
            'status' => 1,
            'is_operator' => true,
            'is_admin' => true,
            'created_at' => '2026-04-15 08:30:00',
            'updated_at' => '2026-04-15 09:45:00',
        ], $payload);
    }
}
