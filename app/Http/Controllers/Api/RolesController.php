<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\RoleDataHelper;
use App\Helpers\RoleQueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoleIndexRequest;
use App\Http\Requests\Api\StoreRoleRequest;
use App\Http\Requests\Api\UpdateRolePermissionRequest;
use App\Http\Requests\Api\UpdateRoleRequest;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    public function index(RoleIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $roles = DB::table('roles')
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id))
                ->when($user?->area_id === null && $request->filled('area_id'), fn ($query) => $query->where('area_id', $request->integer('area_id')))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('display_name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id')
                ->paginate($perPage)
                ->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', collect($roles->items())->map(
                fn ($row) => RoleDataHelper::transform($row)
            )->all(), [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve roles');
        }
    }

    public function show(int $role)
    {
        try {
            $row = RoleQueryHelper::findForUser($role, auth('api')->user()?->area_id);

            if (! $row) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', RoleDataHelper::transform(
                $row,
                RoleQueryHelper::permissions($role)
            ));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve role');
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $user?->area_id ?? $request->input('area_id');
            $roleName = RoleQueryHelper::roleName((string) $request->input('name'), $areaId !== null ? (int) $areaId : null);

            if (DB::table('roles')->where('name', $roleName)->exists()) {
                return ApiResponseHelper::error('Bad request', [
                    'name' => ['The name has already been taken.'],
                ], 400);
            }

            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleName,
                'display_name' => $request->string('display_name')->toString(),
                'guard_name' => $request->input('guard_name', 'api'),
                'area_id' => $areaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $role = DB::table('roles')->where('id', $roleId)->first();

            return ApiResponseHelper::success('Role created successfully', RoleDataHelper::transform($role), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create role');
        }
    }

    public function update(UpdateRoleRequest $request, int $role)
    {
        try {
            $existing = RoleQueryHelper::findForUser($role, auth('api')->user()?->area_id);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $user = auth('api')->user();
            $payload = [
                'display_name' => $request->string('display_name')->toString(),
                'guard_name' => $request->input('guard_name', $existing->guard_name ?? 'api'),
                'updated_at' => now(),
            ];

            if ($user?->area_id === null && $request->has('area_id')) {
                $payload['area_id'] = $request->input('area_id');
            }

            DB::table('roles')->where('id', $role)->update($payload);
            $updated = DB::table('roles')->where('id', $role)->first();

            return ApiResponseHelper::success('Role updated successfully', RoleDataHelper::transform($updated));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update role');
        }
    }

    public function destroy(int $role)
    {
        try {
            $existing = RoleQueryHelper::findForUser($role, auth('api')->user()?->area_id);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            DB::table('roles')->where('id', $role)->delete();

            return ApiResponseHelper::success('Role deleted successfully', RoleDataHelper::transform($existing));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete role');
        }
    }

    public function permissions(int $role)
    {
        try {
            $existing = RoleQueryHelper::findForUser($role, auth('api')->user()?->area_id);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $rolePermissions = RoleQueryHelper::permissions($role);
            $rolePermissionIds = collect($rolePermissions)->pluck('id')->all();
            $permissions = DB::table('permissions')
                ->orderBy('name')
                ->get()
                ->map(function ($permission) use ($rolePermissionIds) {
                    $item = RoleDataHelper::permissionTransform($permission);
                    $item['selected'] = in_array($item['id'], $rolePermissionIds, true);

                    return $item;
                })
                ->values()
                ->all();

            return ApiResponseHelper::success('Data retrieved successfully', [
                'role' => RoleDataHelper::transform($existing, $rolePermissions),
                'permissions' => $permissions,
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve role permissions');
        }
    }

    public function updatePermissions(UpdateRolePermissionRequest $request, int $role)
    {
        try {
            $existing = RoleQueryHelper::findForUser($role, auth('api')->user()?->area_id);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if (! DB::getSchemaBuilder()->hasTable('role_has_permissions')) {
                return ApiResponseHelper::error('Role permission table is not available');
            }

            DB::transaction(function () use ($request, $role) {
                DB::table('role_has_permissions')->where('role_id', $role)->delete();

                foreach ($request->input('permission_ids', []) as $permissionId) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $role,
                        'permission_id' => $permissionId,
                    ]);
                }
            });

            $updated = DB::table('roles')->where('id', $role)->first();

            return ApiResponseHelper::success('Role permissions updated successfully', RoleDataHelper::transform(
                $updated,
                RoleQueryHelper::permissions($role)
            ));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update role permissions');
        }
    }
}
