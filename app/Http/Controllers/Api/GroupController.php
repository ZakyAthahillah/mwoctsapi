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
    public function groupActive(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $groupsQuery = Group::query()
                ->with('area')
                ->where('status', '<>', 11)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
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
            return ApiResponseHelper::error('Failed to retrieve active groups');
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $groupsQuery = Group::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
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
                    'area_id' => auth('api')->user()?->area_id,
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
            $user = auth('api')->user();
            if ((int) $group->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }
            if ((int) $group->area_id !== (int) $user?->area_id) {
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

    public function groupSetstatus(Group $group)
    {
        try {
            if (! in_array((int) $group->status, [1, 99], true)) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['Group status must be 1 or 99 to be toggled.'],
                ], 400);
            }

            $group->update([
                'status' => (int) $group->status === 99 ? 1 : 99,
            ]);

            $group->refresh()->load('area');

            return ApiResponseHelper::success('Group status updated successfully', GroupDataHelper::transform($group));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update group status');
        }
    }
}
