<?php

namespace App\Helpers;

use App\Models\User;

class AuthProfileDataHelper
{
    public static function transform(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'image' => $user->image,
            'area' => $user->area !== null ? [
                'id' => (string) $user->area->id,
                'name' => $user->area->name,
                'object_name' => $user->area->object_name,
            ] : null,
        ];
    }
}
