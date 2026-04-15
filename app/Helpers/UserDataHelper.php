<?php

namespace App\Helpers;

use App\Models\User;

class UserDataHelper
{
    public static function transform(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'created_at' => optional($user->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($user->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
