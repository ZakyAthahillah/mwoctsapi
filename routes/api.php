<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InformantController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::get('/informants', [InformantController::class, 'index']);
    Route::get('/informants/{informant}', [InformantController::class, 'show']);
    Route::get('/machines', [MachineController::class, 'index']);
    Route::get('/machines/{machine}', [MachineController::class, 'show']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{area}', [AreaController::class, 'update']);
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
    Route::post('/informants', [InformantController::class, 'store']);
    Route::put('/informants/{informant}', [InformantController::class, 'update']);
    Route::delete('/informants/{informant}', [InformantController::class, 'destroy']);
    Route::post('/machines', [MachineController::class, 'store']);
    Route::put('/machines/{machine}', [MachineController::class, 'update']);
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);

    Route::middleware('admin')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
