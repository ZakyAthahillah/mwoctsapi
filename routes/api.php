<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\DowntimeController;
use App\Http\Controllers\Api\FbdtController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InformantController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReasonController;
use App\Http\Controllers\Api\SerialNumberController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    // Authenticated session routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Downtime routes
    Route::get('/downtimes', [DowntimeController::class, 'index']);

    // FBDT routes
    Route::get('/fbdts', [FbdtController::class, 'index']);
    Route::get('/fbdts/check', [FbdtController::class, 'check']);
    Route::get('/fbdts/{year}', [FbdtController::class, 'show']);
    Route::post('/fbdts', [FbdtController::class, 'store']);
    Route::put('/fbdts/{year}', [FbdtController::class, 'update']);

    // Area routes
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{area}', [AreaController::class, 'update']);
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

    // Division routes
    Route::get('/divisions', [DivisionController::class, 'index']);
    Route::get('/divisions/{division}', [DivisionController::class, 'show']);
    Route::post('/divisions', [DivisionController::class, 'store']);
    Route::put('/divisions/{division}', [DivisionController::class, 'update']);
    Route::delete('/divisions/{division}', [DivisionController::class, 'destroy']);

    // Informant routes
    Route::get('/informants', [InformantController::class, 'index']);
    Route::get('/informants/{informant}', [InformantController::class, 'show']);
    Route::post('/informants', [InformantController::class, 'store']);
    Route::put('/informants/{informant}', [InformantController::class, 'update']);
    Route::delete('/informants/{informant}', [InformantController::class, 'destroy']);

    // Machine routes
    Route::get('/machines', [MachineController::class, 'index']);
    Route::get('/machines/{machine}', [MachineController::class, 'show']);
    Route::post('/machines', [MachineController::class, 'store']);
    Route::put('/machines/{machine}', [MachineController::class, 'update']);
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);

    // Operation routes
    Route::get('/operations', [OperationController::class, 'index']);
    Route::get('/operations/{operation}', [OperationController::class, 'show']);
    Route::post('/operations', [OperationController::class, 'store']);
    Route::put('/operations/{operation}', [OperationController::class, 'update']);
    Route::delete('/operations/{operation}', [OperationController::class, 'destroy']);

    // Part routes
    Route::get('/parts', [PartController::class, 'index']);
    Route::get('/parts/{part}', [PartController::class, 'show']);
    Route::post('/parts', [PartController::class, 'store']);
    Route::put('/parts/{part}', [PartController::class, 'update']);
    Route::delete('/parts/{part}', [PartController::class, 'destroy']);

    // Position routes
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

    // Reason routes
    Route::get('/reasons', [ReasonController::class, 'index']);
    Route::get('/reasons/{reason}', [ReasonController::class, 'show']);
    Route::post('/reasons', [ReasonController::class, 'store']);
    Route::put('/reasons/{reason}', [ReasonController::class, 'update']);
    Route::delete('/reasons/{reason}', [ReasonController::class, 'destroy']);

    // Serial number routes
    Route::get('/serial-numbers', [SerialNumberController::class, 'index']);
    Route::get('/serial-numbers/{serialNumber}', [SerialNumberController::class, 'show']);
    Route::post('/serial-numbers', [SerialNumberController::class, 'store']);
    Route::put('/serial-numbers/{serialNumber}', [SerialNumberController::class, 'update']);
    Route::get('/serial-numbers/first/{partSerialNumber}', [SerialNumberController::class, 'first']);
    Route::put('/serial-numbers/first/{partSerialNumber}', [SerialNumberController::class, 'updateFirst']);

    // Shift routes
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/shifts/{shift}', [ShiftController::class, 'show']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);

    // Technician routes
    Route::get('/technicians', [TechnicianController::class, 'index']);
    Route::get('/technicians/{technician}', [TechnicianController::class, 'show']);
    Route::post('/technicians', [TechnicianController::class, 'store']);
    Route::put('/technicians/{technician}', [TechnicianController::class, 'update']);
    Route::delete('/technicians/{technician}', [TechnicianController::class, 'destroy']);

    // Group routes
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

    // Admin-only user management routes
    Route::middleware('admin')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
