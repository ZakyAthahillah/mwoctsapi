<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\JobDataHelper;
use App\Helpers\JobWorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JobApproveRequest;
use App\Http\Requests\Api\JobExtendRequest;
use App\Http\Requests\Api\JobFinishRequest;
use App\Http\Requests\Api\JobIndexRequest;
use App\Http\Requests\Api\JobStartRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function index(JobIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $areaId = $user?->area_id;

            $statusCode = JobWorkflowHelper::statusCodeFromFilter($request->input('status'));
            if ($request->filled('status') && $statusCode === null) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['Invalid status filter provided.'],
                ], 400);
            }

            $jobs = DB::table('reportings')
                ->leftJoin('divisions', 'divisions.id', '=', 'reportings.division_id')
                ->leftJoin('shifts as shift_reporting', 'shift_reporting.id', '=', 'reportings.shift_id_reporting')
                ->leftJoin('shifts as shift_start', 'shift_start.id', '=', 'reportings.shift_id_start')
                ->leftJoin('shifts as shift_finish', 'shift_finish.id', '=', 'reportings.shift_id_finish')
                ->leftJoin('shifts as shift_approved', 'shift_approved.id', '=', 'reportings.shift_id_approved')
                ->leftJoin('machines', 'machines.id', '=', 'reportings.machine_id')
                ->leftJoin('positions', 'positions.id', '=', 'reportings.position_id')
                ->leftJoin('parts', 'parts.id', '=', 'reportings.part_id')
                ->leftJoin('operations', 'operations.id', '=', 'reportings.operation_id')
                ->leftJoin('operations as operations_act', 'operations_act.id', '=', 'reportings.operation_id_actual')
                ->leftJoin('reasons', 'reasons.id', '=', 'reportings.reason_id')
                ->leftJoin('informants', 'informants.id', '=', 'reportings.informant_id')
                ->leftJoin('informants as approved', 'approved.id', '=', 'reportings.approved_by')
                ->leftJoin('groups', 'groups.id', '=', 'informants.group_id')
                ->leftJoin('part_serial_numbers', 'part_serial_numbers.id', '=', 'reportings.part_serial_number_id')
                ->select([
                    'reportings.*',
                    'divisions.name as division_name',
                    'shift_reporting.name as shift_reporting_name',
                    'shift_start.name as shift_start_name',
                    'shift_finish.name as shift_finish_name',
                    'shift_approved.name as shift_approved_name',
                    'machines.name as machine_name',
                    'positions.name as position_name',
                    'parts.name as part_name',
                    'operations.name as operation_name',
                    'operations_act.name as operation_actual_name',
                    'reasons.name as reason_name',
                    'informants.name as informant_name',
                    'approved.name as approved_name',
                    'part_serial_numbers.serial_number as serial_number',
                    'groups.name as group_name',
                ])
                ->where('reportings.status', '<>', 99)
                ->when($areaId !== null, fn ($query) => $query->where('reportings.area_id', $areaId), fn ($query) => $query->whereNull('reportings.area_id'))
                ->when($statusCode !== null, fn ($query) => $query->where('reportings.status', $statusCode))
                ->when($request->filled('division_id'), fn ($query) => $query->where('reportings.division_id', $request->integer('division_id')))
                ->when($request->filled('machine_id'), fn ($query) => $query->where('reportings.machine_id', $request->integer('machine_id')))
                ->when($request->filled('position_id'), fn ($query) => $query->where('reportings.position_id', $request->integer('position_id')))
                ->when($request->filled('part_id'), fn ($query) => $query->where('reportings.part_id', $request->integer('part_id')))
                ->when($request->filled('operation_id'), fn ($query) => $query->where('reportings.operation_id', $request->integer('operation_id')))
                ->when($request->filled('reason_id'), fn ($query) => $query->where('reportings.reason_id', $request->integer('reason_id')))
                ->when($request->filled('informant_id'), fn ($query) => $query->where('reportings.informant_id', $request->integer('informant_id')))
                ->when($request->filled('reporting_type'), fn ($query) => $query->where('reportings.reporting_type', $request->integer('reporting_type')))
                ->when($request->filled('technician_id'), function ($query) use ($request) {
                    $query->whereIn('reportings.id', function ($subQuery) use ($request) {
                        $subQuery->select('reporting_id')
                            ->from('reporting_technician')
                            ->where('technician_id', $request->integer('technician_id'));
                    });
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->input('search'));
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('reportings.reporting_number', 'like', '%'.$search.'%')
                            ->orWhere('machines.name', 'like', '%'.$search.'%')
                            ->orWhere('parts.name', 'like', '%'.$search.'%')
                            ->orWhere('positions.name', 'like', '%'.$search.'%')
                            ->orWhere('informants.name', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('reportings.id')
                ->paginate($perPage)
                ->appends($request->query());

            $items = collect($jobs->items());
            $technicianNames = [];

            if ($items->isNotEmpty() && DB::getSchemaBuilder()->hasTable('reporting_technician')) {
                $technicianRows = DB::table('reporting_technician')
                    ->join('technicians', 'technicians.id', '=', 'reporting_technician.technician_id')
                    ->whereIn('reporting_technician.reporting_id', $items->pluck('id')->all())
                    ->orderBy('technicians.name')
                    ->get([
                        'reporting_technician.reporting_id',
                        'technicians.name',
                    ])
                    ->groupBy('reporting_id');

                foreach ($technicianRows as $reportingId => $rows) {
                    $technicianNames[$reportingId] = $rows->pluck('name')->all();
                }
            }

            return ApiResponseHelper::success('Data retrieved successfully', $items->map(
                fn ($row) => JobDataHelper::transform($row, $technicianNames[$row->id] ?? [])
            )->all(), [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve jobs');
        }
    }

    public function show(int $job)
    {
        try {
            $user = auth('api')->user();
            $row = DB::table('reportings')
                ->leftJoin('divisions', 'divisions.id', '=', 'reportings.division_id')
                ->leftJoin('shifts as shift_reporting', 'shift_reporting.id', '=', 'reportings.shift_id_reporting')
                ->leftJoin('shifts as shift_start', 'shift_start.id', '=', 'reportings.shift_id_start')
                ->leftJoin('shifts as shift_finish', 'shift_finish.id', '=', 'reportings.shift_id_finish')
                ->leftJoin('shifts as shift_approved', 'shift_approved.id', '=', 'reportings.shift_id_approved')
                ->leftJoin('machines', 'machines.id', '=', 'reportings.machine_id')
                ->leftJoin('positions', 'positions.id', '=', 'reportings.position_id')
                ->leftJoin('parts', 'parts.id', '=', 'reportings.part_id')
                ->leftJoin('operations', 'operations.id', '=', 'reportings.operation_id')
                ->leftJoin('operations as operations_act', 'operations_act.id', '=', 'reportings.operation_id_actual')
                ->leftJoin('reasons', 'reasons.id', '=', 'reportings.reason_id')
                ->leftJoin('informants', 'informants.id', '=', 'reportings.informant_id')
                ->leftJoin('informants as approved', 'approved.id', '=', 'reportings.approved_by')
                ->leftJoin('groups', 'groups.id', '=', 'informants.group_id')
                ->leftJoin('part_serial_numbers', 'part_serial_numbers.id', '=', 'reportings.part_serial_number_id')
                ->where('reportings.id', $job)
                ->where('reportings.status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('reportings.area_id', $user->area_id), fn ($query) => $query->whereNull('reportings.area_id'))
                ->select([
                    'reportings.*',
                    'divisions.name as division_name',
                    'shift_reporting.name as shift_reporting_name',
                    'shift_start.name as shift_start_name',
                    'shift_finish.name as shift_finish_name',
                    'shift_approved.name as shift_approved_name',
                    'machines.name as machine_name',
                    'positions.name as position_name',
                    'parts.name as part_name',
                    'operations.name as operation_name',
                    'operations_act.name as operation_actual_name',
                    'reasons.name as reason_name',
                    'informants.name as informant_name',
                    'approved.name as approved_name',
                    'part_serial_numbers.serial_number as serial_number',
                    'groups.name as group_name',
                ])
                ->first();

            if (! $row) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $technicianNames = [];
            if (DB::getSchemaBuilder()->hasTable('reporting_technician')) {
                $technicianNames = DB::table('reporting_technician')
                    ->join('technicians', 'technicians.id', '=', 'reporting_technician.technician_id')
                    ->where('reporting_technician.reporting_id', $job)
                    ->orderBy('technicians.name')
                    ->pluck('technicians.name')
                    ->all();
            }

            return ApiResponseHelper::success('Data retrieved successfully', JobDataHelper::transform($row, $technicianNames));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve job detail');
        }
    }

    public function start(JobStartRequest $request, int $job)
    {
        try {
            $user = auth('api')->user();
            $reporting = DB::table('reportings')->where('id', $job)->first();

            if (! $reporting) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if ($user?->area_id !== null && (int) $reporting->area_id !== (int) $user->area_id) {
                return ApiResponseHelper::error('Forbidden', [
                    'auth' => ['You do not have permission to access this job.'],
                ], 403);
            }

            if ((int) $reporting->status !== 1) {
                return ApiResponseHelper::error('Bad request', [
                    'job' => ['Job must be in new status before it can be started.'],
                ], 400);
            }

            $processingDateStart = Carbon::parse((string) $request->input('processing_date_start'))->format('Y-m-d H:i:s');
            $shiftIdStart = JobWorkflowHelper::resolveShiftId((int) $reporting->area_id, $processingDateStart);

            if ($shiftIdStart === null) {
                return ApiResponseHelper::error('Bad request', [
                    'processing_date_start' => ['No active shift matched the provided processing start date.'],
                ], 400);
            }

            DB::transaction(function () use ($job, $reporting, $request, $user, $processingDateStart, $shiftIdStart) {
                $processingId = DB::table('processings')->insertGetId([
                    'area_id' => $user?->area_id ?? $reporting->area_id,
                    'reporting_id' => $job,
                    'processing_date_start' => $processingDateStart,
                    'shift_id_start' => $shiftIdStart,
                    'status' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                JobWorkflowHelper::syncTechnicians('processing_technician', 'processing_id', $processingId, [
                    (int) $request->integer('technician_id'),
                ]);

                $gapTimeResponse = gmdate('H:i:s', max(
                    0,
                    Carbon::parse((string) $reporting->reporting_date)->diffInSeconds(Carbon::parse($processingDateStart))
                ));

                DB::table('reportings')->where('id', $job)->update([
                    'shift_id_start' => $shiftIdStart,
                    'processing_date_start' => $processingDateStart,
                    'status' => 2,
                    'gap_time_response' => $gapTimeResponse,
                ]);

                if (DB::getSchemaBuilder()->hasTable('reporting_technician')) {
                    JobWorkflowHelper::syncTechnicians('reporting_technician', 'reporting_id', $job, [
                        (int) $request->integer('technician_id'),
                    ]);
                }
            });

            return $this->show($job);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to start job');
        }
    }

    public function startExtend(JobStartRequest $request, int $job)
    {
        try {
            $user = auth('api')->user();
            $reporting = DB::table('reportings')->where('id', $job)->first();

            if (! $reporting) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if ($user?->area_id !== null && (int) $reporting->area_id !== (int) $user->area_id) {
                return ApiResponseHelper::error('Forbidden', [
                    'auth' => ['You do not have permission to access this job.'],
                ], 403);
            }

            if ((int) $reporting->status !== 3) {
                return ApiResponseHelper::error('Bad request', [
                    'job' => ['Job must be in extend status before it can be restarted.'],
                ], 400);
            }

            $processingDateStart = Carbon::parse((string) $request->input('processing_date_start'))->format('Y-m-d H:i:s');
            $shiftIdStart = JobWorkflowHelper::resolveShiftId((int) $reporting->area_id, $processingDateStart);

            if ($shiftIdStart === null) {
                return ApiResponseHelper::error('Bad request', [
                    'processing_date_start' => ['No active shift matched the provided processing start date.'],
                ], 400);
            }

            DB::transaction(function () use ($job, $reporting, $request, $user, $processingDateStart, $shiftIdStart) {
                $processingId = DB::table('processings')->insertGetId([
                    'area_id' => $user?->area_id ?? $reporting->area_id,
                    'reporting_id' => $job,
                    'processing_date_start' => $processingDateStart,
                    'shift_id_start' => $shiftIdStart,
                    'status' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                JobWorkflowHelper::syncTechnicians('processing_technician', 'processing_id', $processingId, [
                    (int) $request->integer('technician_id'),
                ]);

                DB::table('reportings')->where('id', $job)->update([
                    'processing_date_finish' => null,
                    'shift_id_start' => $shiftIdStart,
                    'status' => 2,
                ]);

                if (DB::getSchemaBuilder()->hasTable('reporting_technician')) {
                    $existingIds = DB::table('reporting_technician')
                        ->where('reporting_id', $job)
                        ->pluck('technician_id')
                        ->map(fn ($value) => (int) $value)
                        ->all();

                    $technicianIds = JobWorkflowHelper::uniqueTechnicianIds([
                        ...$existingIds,
                        (int) $request->integer('technician_id'),
                    ]);

                    JobWorkflowHelper::syncTechnicians('reporting_technician', 'reporting_id', $job, $technicianIds);
                }
            });

            return $this->show($job);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to restart extended job');
        }
    }

    public function finish(JobFinishRequest $request, int $job)
    {
        try {
            $user = auth('api')->user();
            $reporting = DB::table('reportings')->where('id', $job)->first();

            if (! $reporting) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if ($user?->area_id !== null && (int) $reporting->area_id !== (int) $user->area_id) {
                return ApiResponseHelper::error('Forbidden', [
                    'auth' => ['You do not have permission to access this job.'],
                ], 403);
            }

            if ((int) $reporting->status !== 2) {
                return ApiResponseHelper::error('Bad request', [
                    'job' => ['Job must be in on progress status before it can be finished.'],
                ], 400);
            }

            $isChangePart = $request->boolean('is_change_part');
            if ($isChangePart && ! $request->filled('part_serial_number_id')) {
                return ApiResponseHelper::error('Bad request', [
                    'part_serial_number_id' => ['Part serial number is required when changing part.'],
                ], 400);
            }

            $processingDateFinish = Carbon::parse((string) $request->input('processing_date_finish'))->format('Y-m-d H:i:s');
            $shiftIdFinish = JobWorkflowHelper::resolveShiftId((int) $reporting->area_id, $processingDateFinish);

            if ($shiftIdFinish === null) {
                return ApiResponseHelper::error('Bad request', [
                    'processing_date_finish' => ['No active shift matched the provided processing finish date.'],
                ], 400);
            }

            DB::transaction(function () use ($job, $reporting, $request, $user, $processingDateFinish, $shiftIdFinish, $isChangePart) {
                $shiftIdStart = $reporting->shift_id_start ?: JobWorkflowHelper::resolveShiftId(
                    (int) $reporting->area_id,
                    (string) $reporting->processing_date_start
                );

                DB::table('processings')->insert([
                    'area_id' => $user?->area_id ?? $reporting->area_id,
                    'reporting_id' => $job,
                    'processing_date_start' => $reporting->processing_date_start,
                    'processing_date_finish' => $processingDateFinish,
                    'shift_id_start' => $shiftIdStart,
                    'shift_id_finish' => $shiftIdFinish,
                    'notes' => $request->input('notes'),
                    'status' => 4,
                    'part_serial_number_id_new' => $isChangePart ? $request->integer('part_serial_number_id') : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalDuration = gmdate('H:i:s', max(
                    0,
                    Carbon::parse((string) $reporting->processing_date_start)->diffInSeconds(Carbon::parse($processingDateFinish))
                ));

                $updatePayload = [
                    'shift_id_finish' => $shiftIdFinish,
                    'processing_date_finish' => $processingDateFinish,
                    'status' => 4,
                    'total_time_finishing' => $totalDuration,
                    'notes' => $request->input('notes'),
                    'operation_id_actual' => $request->integer('operation_id_actual'),
                ];

                if ($isChangePart) {
                    $updatePayload['part_serial_number_id_new'] = $request->integer('part_serial_number_id');
                }

                DB::table('reportings')->where('id', $job)->update($updatePayload);

                if ($isChangePart) {
                    $serialNumber = DB::table('serial_numbers')
                        ->where('area_id', $reporting->area_id)
                        ->where('machine_id', $reporting->machine_id)
                        ->where('position_id', $reporting->position_id)
                        ->where('part_id', $reporting->part_id)
                        ->first();

                    if ($serialNumber) {
                        DB::table('serial_numbers')->where('id', $serialNumber->id)->update([
                            'part_serial_number_id' => $request->integer('part_serial_number_id'),
                        ]);

                        $serialNumberId = $serialNumber->id;
                    } else {
                        $serialNumberId = DB::table('serial_numbers')->insertGetId([
                            'area_id' => $reporting->area_id,
                            'machine_id' => $reporting->machine_id,
                            'position_id' => $reporting->position_id,
                            'part_id' => $reporting->part_id,
                            'part_serial_number_id' => $request->integer('part_serial_number_id'),
                        ]);
                    }

                    DB::table('serial_number_logs')->insert([
                        'area_id' => $reporting->area_id,
                        'machine_id' => $reporting->machine_id,
                        'position_id' => $reporting->position_id,
                        'part_id' => $reporting->part_id,
                        'part_serial_number_id' => $request->integer('part_serial_number_id'),
                        'updatedBy' => $user?->id,
                        'updatedDate' => now(),
                        'action' => 3,
                    ]);

                    DB::table('serial_numbers')->where('id', $serialNumberId)->update([
                        'part_serial_number_id' => $request->integer('part_serial_number_id'),
                    ]);
                }
            });

            return $this->show($job);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to finish job');
        }
    }

    public function extend(JobExtendRequest $request, int $job)
    {
        try {
            $user = auth('api')->user();
            $reporting = DB::table('reportings')->where('id', $job)->first();

            if (! $reporting) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if ($user?->area_id !== null && (int) $reporting->area_id !== (int) $user->area_id) {
                return ApiResponseHelper::error('Forbidden', [
                    'auth' => ['You do not have permission to access this job.'],
                ], 403);
            }

            if ((int) $reporting->status !== 2) {
                return ApiResponseHelper::error('Bad request', [
                    'job' => ['Job must be in on progress status before it can be extended.'],
                ], 400);
            }

            $processingDateFinish = Carbon::parse((string) $request->input('processing_date_finish'))->format('Y-m-d H:i:s');
            $shiftIdFinish = JobWorkflowHelper::resolveShiftId((int) $reporting->area_id, $processingDateFinish);

            if ($shiftIdFinish === null) {
                return ApiResponseHelper::error('Bad request', [
                    'processing_date_finish' => ['No active shift matched the provided processing finish date.'],
                ], 400);
            }

            DB::transaction(function () use ($job, $reporting, $request, $user, $processingDateFinish, $shiftIdFinish) {
                $shiftIdStart = $reporting->shift_id_start ?: JobWorkflowHelper::resolveShiftId(
                    (int) $reporting->area_id,
                    (string) $reporting->processing_date_start
                );

                DB::table('processings')->insert([
                    'area_id' => $user?->area_id ?? $reporting->area_id,
                    'reporting_id' => $job,
                    'processing_date_start' => $reporting->processing_date_start,
                    'processing_date_finish' => $processingDateFinish,
                    'shift_id_start' => $shiftIdStart,
                    'shift_id_finish' => $shiftIdFinish,
                    'notes' => $request->input('notes'),
                    'status' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('reportings')->where('id', $job)->update([
                    'shift_id_finish' => $shiftIdFinish,
                    'status' => 3,
                    'notes' => $request->input('notes'),
                ]);
            });

            return $this->show($job);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to extend job');
        }
    }

    public function approve(JobApproveRequest $request, int $job)
    {
        try {
            $user = auth('api')->user();
            $reporting = DB::table('reportings')->where('id', $job)->first();

            if (! $reporting) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            if ($user?->area_id !== null && (int) $reporting->area_id !== (int) $user->area_id) {
                return ApiResponseHelper::error('Forbidden', [
                    'auth' => ['You do not have permission to access this job.'],
                ], 403);
            }

            if ((int) $reporting->status !== 4) {
                return ApiResponseHelper::error('Bad request', [
                    'job' => ['Job must be waiting for approval before it can be approved.'],
                ], 400);
            }

            $approvedAt = Carbon::parse((string) $request->input('approved_at'))->format('Y-m-d H:i:s');
            $shiftIdApproved = JobWorkflowHelper::resolveShiftId((int) $reporting->area_id, $approvedAt);

            if ($shiftIdApproved === null) {
                return ApiResponseHelper::error('Bad request', [
                    'approved_at' => ['No active shift matched the provided approval date.'],
                ], 400);
            }

            DB::table('reportings')->where('id', $job)->update([
                'shift_id_approved' => $shiftIdApproved,
                'approved_at' => $approvedAt,
                'approved_by' => $request->integer('approved_by'),
                'approved_notes' => $request->input('approved_notes'),
                'total_time_approved' => gmdate('H:i:s', max(
                    0,
                    Carbon::parse((string) $reporting->processing_date_finish)->diffInSeconds(Carbon::parse($approvedAt))
                )),
                'status' => 5,
            ]);

            return $this->show($job);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to approve job');
        }
    }
}
