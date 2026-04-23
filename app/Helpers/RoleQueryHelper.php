<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class RoleQueryHelper
{
    public static function findForUser(int $role, ?int $areaId): ?object
    {
        return DB::table('roles')
            ->where('id', $role)
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId))
            ->first();
    }

    public static function permissions(int $role): array
    {
        if (! DB::getSchemaBuilder()->hasTable('role_has_permissions')) {
            return [];
        }

        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $role)
            ->orderBy('permissions.name')
            ->get(['permissions.id', 'permissions.name', 'permissions.guard_name'])
            ->map(fn ($permission) => RoleDataHelper::permissionTransform($permission))
            ->all();
    }

    public static function roleName(string $name, ?int $areaId): string
    {
        $name = trim($name);

        if ($areaId === null || str_starts_with($name, $areaId.'_')) {
            return $name;
        }

        return $areaId.'_'.$name;
    }
}
