<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);

    Route::middleware('admin')->group(function () {
        Route::post('/areas', [AreaController::class, 'store']);
        Route::put('/areas/{area}', [AreaController::class, 'update']);
        Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
