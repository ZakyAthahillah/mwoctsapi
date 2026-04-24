<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\ReportingDataHelper;
use App\Helpers\ReportingQueryHelper;
use App\Helpers\ReportingShiftHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportingIndexRequest;
use App\Http\Requests\Api\ReportingTimeRequest;
use App\Http\Requests\Api\StoreReportingRequest;
use App\Http\Requests\Api\UpdateReportingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportingController extends Controller
{
    public function index(ReportingIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $reportings = ReportingQueryHelper::baseQuery()
                ->whereNotIn('reportings.status', [1, 99])
                ->when($user?->area_id !== null, fn ($query) => $query->where('reportings.area_id', $user->area_id), fn ($query) => $query->whereNull('reportings.area_id'))
                ->when($request->filled('division_id'), fn ($query) => $query->where('reportings.division_id', $request->integer('division_id')))
                ->when($request->filled('machine_id'), fn ($query) => $query->where('reportings.machine_id', $request->integer('machine_id')))
                ->when($request->filled('position_id'), fn ($query) => $query->where('reportings.position_id', $request->integer('position_id')))
                ->when($request->filled('part_id'), fn ($query) => $query->where('reportings.part_id', $request->integer('part_id')))
                ->when($request->filled('part_serial_number_id'), fn ($query) => $query->where('reportings.part_serial_number_id', $request->integer('part_serial_number_id')))
                ->when($request->filled('operation_id'), fn ($query) => $query->where('reportings.operation_id', $request->integer('operation_id')))
                ->when($request->filled('reason_id'), fn ($query) => $query->where('reportings.reason_id', $request->integer('reason_id')))
                ->when($request->filled('informant_id'), fn ($query) => $query->where('reportings.informant_id', $request->integer('informant_id')))
                ->when($request->filled('reporting_type'), fn ($query) => $query->where('reportings.reporting_type', $request->integer('reporting_type')))
                ->when($request->filled('status'), fn ($query) => $query->where('reportings.status', $request->integer('status')))
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->input('search'));
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('reportings.reporting_number', 'like', '%'.$search.'%')
                            ->orWhere('machines.name', 'like', '%'.$search.'%')
                            ->orWhere('positions.name', 'like', '%'.$search.'%')
                            ->orWhere('parts.name', 'like', '%'.$search.'%')
                            ->orWhere('informants.name', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('reportings.id')
                ->paginate($perPage)
                ->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', collect($reportings->items())->map(
                fn ($row) => ReportingDataHelper::transform($row)
            )->all(), [
                'current_page' => $reportings->currentPage(),
                'last_page' => $reportings->lastPage(),
                'per_page' => $reportings->perPage(),
                'total' => $reportings->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reportings');
        }
    }

    public function store(StoreReportingRequest $request)
    {
        try {
            $user = auth('api')->user();
            $reportingDate = Carbon::parse((string) $request->input('reporting_date'))->format('Y-m-d H:i:s');
            $shift = ReportingShiftHelper::resolve($user?->area_id, $reportingDate);

            if ($shift === null) {
                return ApiResponseHelper::error('Bad request', [
                    'reporting_date' => ['No active shift matched the provided reporting date.'],
                ], 400);
            }

            $reportingId = DB::transaction(function () use ($request, $user, $reportingDate, $shift) {
                $sortOrder = ReportingQueryHelper::nextSortOrder($user?->area_id);

                return DB::table('reportings')->insertGetId([
                    'area_id' => $user?->area_id,
                    'reporting_type' => $request->integer('reporting_type'),
                    'machine_id' => $request->integer('machine_id'),
                    'position_id' => $request->integer('position_id'),
                    'part_id' => $request->integer('part_id'),
                    'part_serial_number_id' => $request->input('part_serial_number_id'),
                    'division_id' => $request->integer('division_id'),
                    'operation_id' => $request->integer('operation_id'),
                    'reason_id' => $request->integer('reason_id'),
                    'reporting_notes' => $request->input('reporting_notes'),
                    'informant_id' => $request->integer('informant_id'),
                    'shift_id_reporting' => $shift->id,
                    'reporting_date' => $reportingDate,
                    'sort_order' => $sortOrder,
                    'reporting_number' => Str::upper(Str::random(6)),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return ApiResponseHelper::success('Reporting created successfully', ReportingDataHelper::transform(ReportingQueryHelper::findForArea($reportingId, $user?->area_id)), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create reporting');
        }
    }

    public function show(int $reporting)
    {
        try {
            $row = ReportingQueryHelper::findForArea($reporting, auth('api')->user()?->area_id);

            if ($row === null) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', ReportingDataHelper::transform($row));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reporting');
        }
    }

    public function update(UpdateReportingRequest $request, int $reporting)
    {
        try {
            $existing = DB::table('reportings')->where('id', $reporting)->first();
            $user = auth('api')->user();

            if ($existing === null || (int) $existing->status === 99 || (int) $existing->area_id !== (int) $user?->area_id) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $reportingDate = Carbon::parse((string) $request->input('reporting_date'))->format('Y-m-d H:i:s');
            $shift = ReportingShiftHelper::resolve($user?->area_id, $reportingDate);

            if ($shift === null) {
                return ApiResponseHelper::error('Bad request', [
                    'reporting_date' => ['No active shift matched the provided reporting date.'],
                ], 400);
            }

            DB::table('reportings')->where('id', $reporting)->update([
                'machine_id' => $request->integer('machine_id'),
                'position_id' => $request->integer('position_id'),
                'part_id' => $request->integer('part_id'),
                'part_serial_number_id' => $request->input('part_serial_number_id'),
                'division_id' => $request->integer('division_id'),
                'operation_id' => $request->integer('operation_id'),
                'reason_id' => $request->integer('reason_id'),
                'reporting_notes' => $request->input('reporting_notes'),
                'informant_id' => $request->integer('informant_id'),
                'reporting_type' => $request->integer('reporting_type'),
                'shift_id_reporting' => $shift->id,
                'reporting_date' => $reportingDate,
                'updated_at' => now(),
            ]);

            return ApiResponseHelper::success('Reporting updated successfully', ReportingDataHelper::transform(ReportingQueryHelper::findForArea($reporting, $user?->area_id)));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update reporting');
        }
    }

    public function destroy(int $reporting)
    {
        try {
            $existing = DB::table('reportings')->where('id', $reporting)->first();
            $user = auth('api')->user();

            if ($existing === null || (int) $existing->status === 99 || (int) $existing->area_id !== (int) $user?->area_id) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            DB::table('reportings')->where('id', $reporting)->update([
                'status' => 99,
                'updated_at' => now(),
            ]);

            return ApiResponseHelper::success('Reporting deleted successfully', ReportingDataHelper::transform(ReportingQueryHelper::findForArea($reporting, $user?->area_id, true)));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete reporting');
        }
    }

    public function types()
    {
        try {
            return ApiResponseHelper::success('Data retrieved successfully', ReportingDataHelper::reportingTypes());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reporting types');
        }
    }

    public function time(ReportingTimeRequest $request)
    {
        try {
            $user = auth('api')->user();
            $dateTime = $request->input('reporting_date') ?? now()->format('Y-m-d H:i:s');
            $shift = ReportingShiftHelper::resolve($user?->area_id, (string) $dateTime);

            return ApiResponseHelper::success('Data retrieved successfully', ReportingShiftHelper::transform($shift, (string) $dateTime));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reporting time');
        }
    }
}
