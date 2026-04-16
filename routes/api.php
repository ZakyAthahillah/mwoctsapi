<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InformantController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\ReasonController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{area}', [AreaController::class, 'update']);
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
    Route::get('/informants', [InformantController::class, 'index']);
    Route::get('/informants/{informant}', [InformantController::class, 'show']);
    Route::post('/informants', [InformantController::class, 'store']);
    Route::put('/informants/{informant}', [InformantController::class, 'update']);
    Route::delete('/informants/{informant}', [InformantController::class, 'destroy']);
    Route::get('/machines', [MachineController::class, 'index']);
    Route::get('/machines/{machine}', [MachineController::class, 'show']);
    Route::post('/machines', [MachineController::class, 'store']);
    Route::put('/machines/{machine}', [MachineController::class, 'update']);
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);
    Route::get('/operations', [OperationController::class, 'index']);
    Route::get('/operations/{operation}', [OperationController::class, 'show']);
    Route::post('/operations', [OperationController::class, 'store']);
    Route::put('/operations/{operation}', [OperationController::class, 'update']);
    Route::delete('/operations/{operation}', [OperationController::class, 'destroy']);
    Route::get('/parts', [PartController::class, 'index']);
    Route::get('/parts/{part}', [PartController::class, 'show']);
    Route::post('/parts', [PartController::class, 'store']);
    Route::put('/parts/{part}', [PartController::class, 'update']);
    Route::delete('/parts/{part}', [PartController::class, 'destroy']);
    Route::get('/reasons', [ReasonController::class, 'index']);
    Route::get('/reasons/{reason}', [ReasonController::class, 'show']);
    Route::post('/reasons', [ReasonController::class, 'store']);
    Route::put('/reasons/{reason}', [ReasonController::class, 'update']);
    Route::delete('/reasons/{reason}', [ReasonController::class, 'destroy']);
    Route::get('/technicians', [TechnicianController::class, 'index']);
    Route::get('/technicians/{technician}', [TechnicianController::class, 'show']);
    Route::post('/technicians', [TechnicianController::class, 'store']);
    Route::put('/technicians/{technician}', [TechnicianController::class, 'update']);
    Route::delete('/technicians/{technician}', [TechnicianController::class, 'destroy']);
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

    Route::middleware('admin')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
