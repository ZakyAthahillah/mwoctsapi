<?php

namespace App\Helpers;

class RoleDataHelper
{
    public static function transform(object $row, array $permissions = []): array
    {
        return [
            'id' => (string) $row->id,
            'name' => $row->name,
            'display_name' => $row->display_name,
            'guard_name' => $row->guard_name ?? null,
            'area_id' => $row->area_id !== null ? (string) $row->area_id : null,
            'permissions' => $permissions,
            'created_at' => $row->created_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->created_at)) : null,
            'updated_at' => $row->updated_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->updated_at)) : null,
        ];
    }

    public static function permissionTransform(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'name' => $row->name,
            'guard_name' => $row->guard_name ?? null,
            'module' => explode('-', (string) $row->name)[0] ?? null,
            'operation' => explode('-', (string) $row->name)[1] ?? null,
        ];
    }
}
