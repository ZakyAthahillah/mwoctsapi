<?php

namespace App\Helpers;

class PermissionDataHelper
{
    public static function transform(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'name' => $row->name,
            'guard_name' => $row->guard_name ?? null,
            'created_at' => $row->created_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->created_at)) : null,
            'updated_at' => $row->updated_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->updated_at)) : null,
        ];
    }
}
