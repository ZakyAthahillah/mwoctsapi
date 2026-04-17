<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\PermissionDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePermissionRequest;
use App\Http\Requests\Api\UpdatePermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $permissions = DB::table('permissions')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('id')
                ->paginate($perPage)
                ->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', collect($permissions->items())->map(
                fn ($row) => PermissionDataHelper::transform($row)
            )->all(), [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve permissions');
        }
    }

    public function show(int $permission)
    {
        try {
            $row = DB::table('permissions')->where('id', $permission)->first();

            if (! $row) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', PermissionDataHelper::transform($row));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve permission');
        }
    }

    public function store(StorePermissionRequest $request)
    {
        try {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $request->string('name')->toString(),
                'guard_name' => $request->input('guard_name', 'api'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permission = DB::table('permissions')->where('id', $permissionId)->first();

            return ApiResponseHelper::success('Permission created successfully', PermissionDataHelper::transform($permission), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create permission');
        }
    }

    public function update(UpdatePermissionRequest $request, int $permission)
    {
        try {
            $existing = DB::table('permissions')->where('id', $permission)->first();

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            DB::table('permissions')->where('id', $permission)->update([
                'name' => $request->string('name')->toString(),
                'guard_name' => $request->input('guard_name', $existing->guard_name ?? 'api'),
                'updated_at' => now(),
            ]);

            $updated = DB::table('permissions')->where('id', $permission)->first();

            return ApiResponseHelper::success('Permission updated successfully', PermissionDataHelper::transform($updated));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update permission');
        }
    }

    public function destroy(int $permission)
    {
        try {
            $existing = DB::table('permissions')->where('id', $permission)->first();

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            DB::table('permissions')->where('id', $permission)->delete();

            return ApiResponseHelper::success('Permission deleted successfully', PermissionDataHelper::transform($existing));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete permission');
        }
    }
}
