<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\GroupDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGroupRequest;
use App\Http\Requests\Api\UpdateGroupRequest;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $groupsQuery = Group::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('id', 'desc');

            $groups = $groupsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $groups->getCollection()->map(
                fn (Group $group) => GroupDataHelper::transform($group)
            )->all(), [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve groups');
        }
    }

    public function store(StoreGroupRequest $request)
    {
        try {
            $group = DB::transaction(function () use ($request) {
                return Group::create([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $group->load('area');

            return ApiResponseHelper::success('Group created successfully', GroupDataHelper::transform($group), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create group');
        }
    }

    public function show(Group $group)
    {
        try {
            if ((int) $group->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $group->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', GroupDataHelper::transform($group));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve group');
        }
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        try {
            if ((int) $group->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Group has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $group) {
                $group->update([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $group->refresh()->load('area');

            return ApiResponseHelper::success('Group updated successfully', GroupDataHelper::transform($group));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update group');
        }
    }

    public function destroy(Group $group)
    {
        try {
            if ((int) $group->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Group has already been deleted.'],
                ], 400);
            }

            $group->update([
                'status' => 99,
            ]);

            $group->refresh()->load('area');

            return ApiResponseHelper::success('Group deleted successfully', GroupDataHelper::transform($group));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete group');
        }
    }
}
