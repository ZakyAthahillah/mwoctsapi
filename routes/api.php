<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\DowntimeController;
use App\Http\Controllers\Api\FbdtController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InformantController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\MtbfController;
use App\Http\Controllers\Api\MttrController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\PermissionsController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReasonController;
use App\Http\Controllers\Api\ReportingController;
use App\Http\Controllers\Api\ReportingReportController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\SerialNumberController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\TargetController;
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
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Monitor routes
    Route::get('/monitor', [MonitorController::class, 'index']);

    // Downtime routes
    Route::get('/downtimes', [DowntimeController::class, 'index']);

    // Reporting routes
    Route::get('/reportings/types', [ReportingController::class, 'types']);
    Route::get('/reportings/time', [ReportingController::class, 'time']);
    Route::get('/reportings', [ReportingController::class, 'index']);
    Route::get('/reportings/{reporting}', [ReportingController::class, 'show']);
    Route::post('/reportings', [ReportingController::class, 'store']);
    Route::put('/reportings/{reporting}', [ReportingController::class, 'update']);
    Route::delete('/reportings/{reporting}', [ReportingController::class, 'destroy']);

    // Reporting report routes
    Route::get('/reporting-reports', [ReportingReportController::class, 'index']);
    Route::get('/reporting-reports/statuses', [ReportingReportController::class, 'statuses']);

    // Job routes
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/new', [JobController::class, 'newJobs']);
    Route::get('/jobs/on-progress', [JobController::class, 'onProgress']);
    Route::get('/jobs/extend', [JobController::class, 'extended']);
    Route::get('/jobs/waiting-for-approval', [JobController::class, 'waitingForApproval']);
    Route::get('/jobs/finish', [JobController::class, 'finished']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::put('/jobs/{job}/start', [JobController::class, 'start']);
    Route::put('/jobs/{job}/start-extend', [JobController::class, 'startExtend']);
    Route::put('/jobs/{job}/finish', [JobController::class, 'finish']);
    Route::put('/jobs/{job}/extend', [JobController::class, 'extend']);
    Route::put('/jobs/{job}/approve', [JobController::class, 'approve']);

    // MTBF routes
    Route::get('/mtbf', [MtbfController::class, 'index']);
    Route::get('/mtbf/taskplus', [MtbfController::class, 'taskplus']);

    // MTTR routes
    Route::get('/mttr', [MttrController::class, 'index']);

    // FBDT routes
    Route::get('/fbdts', [FbdtController::class, 'index']);
    Route::get('/fbdts/check', [FbdtController::class, 'check']);
    Route::get('/fbdts/{year}', [FbdtController::class, 'show']);
    Route::post('/fbdts', [FbdtController::class, 'store']);
    Route::put('/fbdts/{year}', [FbdtController::class, 'update']);

    // Target routes
    Route::get('/targets', [TargetController::class, 'index']);
    Route::get('/targets/check', [TargetController::class, 'check']);
    Route::get('/targets/{part}/{year}', [TargetController::class, 'show']);
    Route::post('/targets', [TargetController::class, 'store']);
    Route::put('/targets/{part}/{year}', [TargetController::class, 'update']);
    Route::delete('/targets/{part}/{year}', [TargetController::class, 'destroy']);

    // Area routes
    Route::get('/areas_active', [AreaController::class, 'areaActive']);
    Route::put('/area_setstatus/{area}', [AreaController::class, 'areaSetstatus']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{area}', [AreaController::class, 'update']);
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

    // Division routes
    Route::get('/division_active', [DivisionController::class, 'divisionActive']);
    Route::put('/division_setstatus/{division}', [DivisionController::class, 'divisionSetstatus']);
    Route::get('/divisions', [DivisionController::class, 'index']);
    Route::get('/divisions/{division}', [DivisionController::class, 'show']);
    Route::post('/divisions', [DivisionController::class, 'store']);
    Route::put('/divisions/{division}', [DivisionController::class, 'update']);
    Route::delete('/divisions/{division}', [DivisionController::class, 'destroy']);

    // Informant routes
    Route::get('/informant_active', [InformantController::class, 'informantActive']);
    Route::put('/informant_setstatus/{informant}', [InformantController::class, 'informantSetstatus']);
    Route::get('/informants', [InformantController::class, 'index']);
    Route::get('/informants/{informant}', [InformantController::class, 'show']);
    Route::post('/informants', [InformantController::class, 'store']);
    Route::put('/informants/{informant}', [InformantController::class, 'update']);
    Route::delete('/informants/{informant}', [InformantController::class, 'destroy']);

    // Machine routes
    Route::get('/machine_active', [MachineController::class, 'machineActive']);
    Route::put('/machine_setstatus/{machine}', [MachineController::class, 'machineSetstatus']);
    Route::get('/machines/full-data-array', [MachineController::class, 'getFullDataArray']);
    Route::get('/machines/full-data-array-job', [MachineController::class, 'getFullDataArrayJob']);
    Route::get('/machines/{machine}/detail', [MachineController::class, 'getDetail']);
    Route::get('/machines/{machine}/positions', [MachineController::class, 'getPosition']);
    Route::get('/machines/{machineId}/positions/{positionId}/detail-job', [MachineController::class, 'getDetailJob']);
    Route::get('/machines/{machineId}/positions/{positionId}/parts', [MachineController::class, 'getPart']);
    Route::put('/machines/{machine}/activate', [MachineController::class, 'activate']);
    Route::get('/machines', [MachineController::class, 'index']);
    Route::get('/machines/{machine}', [MachineController::class, 'show']);
    Route::post('/machines', [MachineController::class, 'store']);
    Route::put('/machines/{machine}', [MachineController::class, 'update']);
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);

    // Legacy machine compatibility routes
    Route::match(['get', 'post'], '/machine/get-full-data-array', [MachineController::class, 'getFullDataArray']);
    Route::match(['get', 'post'], '/machine/get-full-data-array-job', [MachineController::class, 'getFullDataArrayJob']);
    Route::get('/machine/{machine}/get-detail', [MachineController::class, 'getDetail']);
    Route::get('/machine/{machine}/get-position', [MachineController::class, 'getPosition']);
    Route::match(['get', 'post'], '/machine/{machineId}/{positionId}/get-part', [MachineController::class, 'getPart']);
    Route::get('/machine/{machineId}/{positionId}/get-detail-job', [MachineController::class, 'getDetailJob']);
    Route::match(['put', 'post'], '/machine/{machine}/activate', [MachineController::class, 'activate']);

    // Operation routes
    Route::get('/operation_active', [OperationController::class, 'operationActive']);
    Route::put('/operation_setstatus/{operation}', [OperationController::class, 'operationSetstatus']);
    Route::get('/operations', [OperationController::class, 'index']);
    Route::get('/operations/{operation}', [OperationController::class, 'show']);
    Route::post('/operations', [OperationController::class, 'store']);
    Route::put('/operations/{operation}', [OperationController::class, 'update']);
    Route::delete('/operations/{operation}', [OperationController::class, 'destroy']);

    // Part routes
    Route::get('/part_active', [PartController::class, 'partActive']);
    Route::put('/part_setstatus/{part}', [PartController::class, 'partSetstatus']);
    Route::match(['get', 'post'], '/part/get-data-array', [PartController::class, 'getDataArray']);
    Route::match(['get', 'post'], '/part/get-full-data-array', [PartController::class, 'getFullDataArray']);
    Route::get('/part/{part}/detail', [PartController::class, 'getDetail']);
    Route::match(['get', 'post'], '/part/{part}/get-operation', [PartController::class, 'getOperation']);
    Route::get('/parts', [PartController::class, 'index']);
    Route::get('/parts/{part}', [PartController::class, 'show']);
    Route::post('/parts', [PartController::class, 'store']);
    Route::put('/parts/{part}', [PartController::class, 'update']);
    Route::delete('/parts/{part}', [PartController::class, 'destroy']);

    // Position routes
    Route::get('/position_active', [PositionController::class, 'positionActive']);
    Route::put('/position_setstatus/{position}', [PositionController::class, 'positionSetstatus']);
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

    // Reason routes
    Route::get('/reason_active', [ReasonController::class, 'reasonActive']);
    Route::put('/reason_setstatus/{reason}', [ReasonController::class, 'reasonSetstatus']);
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
    Route::get('/shift_active', [ShiftController::class, 'shiftActive']);
    Route::put('/shift_setstatus/{shift}', [ShiftController::class, 'shiftSetstatus']);
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/shifts/{shift}', [ShiftController::class, 'show']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);

    // Technician routes
    Route::get('/technician_active', [TechnicianController::class, 'technicianActive']);
    Route::put('/technician_setstatus/{technician}', [TechnicianController::class, 'technicianSetstatus']);
    Route::get('/technicians', [TechnicianController::class, 'index']);
    Route::get('/technicians/{technician}', [TechnicianController::class, 'show']);
    Route::post('/technicians', [TechnicianController::class, 'store']);
    Route::put('/technicians/{technician}', [TechnicianController::class, 'update']);
    Route::delete('/technicians/{technician}', [TechnicianController::class, 'destroy']);

    // Group routes
    Route::get('/group_active', [GroupController::class, 'groupActive']);
    Route::put('/group_setstatus/{group}', [GroupController::class, 'groupSetstatus']);
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

    // Admin-only user management routes
    Route::middleware('admin')->group(function () {
        Route::get('/roles', [RolesController::class, 'index']);
        Route::get('/roles/{role}', [RolesController::class, 'show']);
        Route::post('/roles', [RolesController::class, 'store']);
        Route::put('/roles/{role}', [RolesController::class, 'update']);
        Route::delete('/roles/{role}', [RolesController::class, 'destroy']);
        Route::get('/roles/{role}/permissions', [RolesController::class, 'permissions']);
        Route::put('/roles/{role}/permissions', [RolesController::class, 'updatePermissions']);

        Route::get('/permissions', [PermissionsController::class, 'index']);
        Route::get('/permissions/{permission}', [PermissionsController::class, 'show']);
        Route::post('/permissions', [PermissionsController::class, 'store']);
        Route::put('/permissions/{permission}', [PermissionsController::class, 'update']);
        Route::delete('/permissions/{permission}', [PermissionsController::class, 'destroy']);

        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/user_setstatus/{user}', [UserController::class, 'userSetstatus']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
