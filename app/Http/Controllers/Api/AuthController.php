<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\AuthProfileDataHelper;
use App\Helpers\UserDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $user = DB::transaction(function () use ($request) {
                return User::create([
                    'area_id' => null,
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'username' => $request->string('username')->toString(),
                    'image' => null,
                    'status' => 1,
                    'password' => $request->string('password')->toString(),
                    'is_operator' => false,
                    'is_admin' => false,
                ]);
            });

            $token = auth('api')->login($user);

            return ApiResponseHelper::success('User registered successfully', [
                'user' => UserDataHelper::transform($user),
                'authorization' => [
                    'type' => 'bearer',
                    'token' => $token,
                    'expires_in_minutes' => (int) config('jwt.ttl'),
                ],
            ], null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to register user');
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();

            if (! $token = auth('api')->attempt($credentials)) {
                return ApiResponseHelper::error('Invalid credentials', [
                    'auth' => ['Email or password is incorrect.'],
                ], 401);
            }

            /** @var User $user */
            $user = auth('api')->user();

            return ApiResponseHelper::success('Login successful', [
                'user' => UserDataHelper::transform($user),
                'authorization' => [
                    'type' => 'bearer',
                    'token' => $token,
                    'expires_in_minutes' => (int) config('jwt.ttl'),
                ],
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to login user');
        }
    }

    public function logout()
    {
        try {
            auth('api')->logout();

            return ApiResponseHelper::success('Logout successful', null);
        } catch (JWTException $exception) {
            return ApiResponseHelper::error('Failed to logout user');
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to logout user');
        }
    }

    public function refresh()
    {
        try {
            $token = auth('api')->refresh();
            auth('api')->setToken($token);

            /** @var User|null $user */
            $user = auth('api')->user();

            return ApiResponseHelper::success('Token refreshed successfully', [
                'user' => $user ? UserDataHelper::transform($user) : null,
                'authorization' => [
                    'type' => 'bearer',
                    'token' => $token,
                    'expires_in_minutes' => (int) config('jwt.ttl'),
                    'refresh_expires_in_minutes' => (int) config('jwt.refresh_ttl'),
                ],
            ]);
        } catch (JWTException $exception) {
            return ApiResponseHelper::error('Unauthorized', [
                'auth' => [$exception->getMessage()],
            ], 401);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to refresh token');
        }
    }

    public function me()
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

            return ApiResponseHelper::success('Data retrieved successfully', AuthProfileDataHelper::transform($user));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve authenticated user profile');
        }
    }
}
