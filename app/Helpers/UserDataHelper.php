<?php

namespace App\Helpers;

use App\Models\User;

class UserDataHelper
{
    public static function transform(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'area_id' => $user->area_id !== null ? (string) $user->area_id : null,
            'area_name' => $user->area?->name,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'image' => $user->image,
            'status' => (int) $user->status,
            'is_operator' => (bool) $user->is_operator,
            'is_admin' => (bool) $user->is_admin,
            'created_at' => optional($user->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($user->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
