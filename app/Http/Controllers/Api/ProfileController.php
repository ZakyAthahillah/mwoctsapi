<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\ProfileImageHelper;
use App\Helpers\UserDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show()
    {
        try {
            /** @var User|null $user */
            $user = auth('api')->user();

            if ($user === null) {
                return ApiResponseHelper::error('Unauthorized', [
                    'auth' => ['User is not authenticated.'],
                ], 401);
            }

            $user->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', UserDataHelper::transform($user));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve profile');
        }
    }

    public function update(UpdateProfileRequest $request)
    {
        try {
            /** @var User|null $user */
            $user = auth('api')->user();

            if ($user === null) {
                return ApiResponseHelper::error('Unauthorized', [
                    'auth' => ['User is not authenticated.'],
                ], 401);
            }

            DB::transaction(function () use ($request, $user) {
                $payload = [
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'username' => $request->string('username')->toString(),
                ];

                if ($request->filled('password')) {
                    $payload['password'] = $request->string('password')->toString();
                }

                if ($request->hasFile('image')) {
                    $payload['image'] = ProfileImageHelper::store($request->file('image'), $user);
                } elseif ($request->exists('image')) {
                    $payload['image'] = $request->input('image');
                }

                $user->update($payload);
            });

            $user->refresh()->load('area');

            return ApiResponseHelper::success('Profile updated successfully', UserDataHelper::transform($user));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update profile');
        }
    }
}
