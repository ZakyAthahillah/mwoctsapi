<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\UserDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
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
