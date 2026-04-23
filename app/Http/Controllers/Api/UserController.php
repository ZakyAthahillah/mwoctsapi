<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\UserDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Requests\Api\UserIndexRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(UserIndexRequest $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $users = User::query()
                ->with('area')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('username', 'like', '%'.$search.'%');
                    });
                })
                ->when($request->filled('area_id'), fn ($query) => $query->where('area_id', $request->integer('area_id')))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->integer('status')))
                ->when($request->has('is_operator'), fn ($query) => $query->where('is_operator', $request->boolean('is_operator')))
                ->when($request->has('is_admin'), fn ($query) => $query->where('is_admin', $request->boolean('is_admin')))
                ->orderBy('id')
                ->paginate($perPage)
                ->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $users->getCollection()->map(
                fn (User $user) => UserDataHelper::transform($user)
            )->all(), [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve users');
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            DB::transaction(function () use ($request, $user) {
                $payload = [
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'username' => $request->string('username')->toString(),
                    'image' => $request->input('image'),
                    'status' => $request->integer('status'),
                    'is_operator' => $request->boolean('is_operator'),
                    'is_admin' => $request->boolean('is_admin'),
                ];

                if ($request->filled('password')) {
                    $payload['password'] = $request->string('password')->toString();
                }

                $user->update($payload);
            });

            $user->refresh()->load('area');

            return ApiResponseHelper::success('User updated successfully', UserDataHelper::transform($user));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update user');
        }
    }

    public function destroy(User $user)
    {
        try {
            if ((int) auth('api')->id() === (int) $user->id) {
                return ApiResponseHelper::error('Bad request', [
                    'user' => ['Admin cannot delete their own account through this endpoint.'],
                ], 400);
            }

            $userData = UserDataHelper::transform($user);

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            return ApiResponseHelper::success('User deleted successfully', $userData);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete user');
        }
    }

    public function userSetstatus(User $user)
    {
        try {
            if (! in_array((int) $user->status, [1, 99], true)) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['User status must be 1 or 99 to be toggled.'],
                ], 400);
            }

            if ((int) auth('api')->id() === (int) $user->id && (int) $user->status === 1) {
                return ApiResponseHelper::error('Bad request', [
                    'user' => ['Admin cannot deactivate their own account through this endpoint.'],
                ], 400);
            }

            DB::transaction(function () use ($user) {
                $user->update([
                    'status' => (int) $user->status === 99 ? 1 : 99,
                ]);
            });

            $user->refresh()->load('area');

            return ApiResponseHelper::success('User status updated successfully', UserDataHelper::transform($user));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update user status');
        }
    }
}
