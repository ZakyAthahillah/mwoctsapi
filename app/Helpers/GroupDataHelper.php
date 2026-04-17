<?php

namespace App\Helpers;

use App\Models\Group;

class GroupDataHelper
{
    public static function transform(Group $group): array
    {
        return [
            'id' => (string) $group->id,
            'area_id' => $group->area_id !== null ? (string) $group->area_id : null,
            'area_name' => $group->area?->name,
            'name' => $group->name,
            'status' => (int) $group->status,
            'created_at' => optional($group->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($group->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
