<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\DowntimeDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DowntimeIndexRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DowntimeController extends Controller
{
    public function index(DowntimeIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $areaId = $user?->area_id;

            $periodStart = $request->input('period_start')
                ? Carbon::parse((string) $request->input('period_start'))->startOfDay()
                : Carbon::now('Asia/Jakarta')->subDays(30)->startOfDay();
            $periodEnd = $request->input('period_end')
                ? Carbon::parse((string) $request->input('period_end'))->endOfDay()
                : Carbon::now('Asia/Jakarta')->endOfDay();

            $baseQuery = DB::table('reportings')
                ->leftJoin('divisions', 'divisions.id', '=', 'reportings.division_id')
                ->leftJoin('shifts as shift_reporting', 'shift_reporting.id', '=', 'reportings.shift_id_reporting')
                ->leftJoin('shifts as shift_start', 'shift_start.id', '=', 'reportings.shift_id_start')
                ->leftJoin('shifts as shift_finish', 'shift_finish.id', '=', 'reportings.shift_id_finish')
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
                ->where('reportings.status', 5)
                ->when($areaId !== null, fn ($query) => $query->where('reportings.area_id', $areaId), fn ($query) => $query->whereNull('reportings.area_id'))
                ->when($request->filled('division_id'), fn ($query) => $query->where('reportings.division_id', $request->integer('division_id')))
                ->when($request->filled('machine_id'), fn ($query) => $query->where('reportings.machine_id', $request->integer('machine_id')))
                ->when($request->filled('position_id'), fn ($query) => $query->where('reportings.position_id', $request->integer('position_id')))
                ->when($request->filled('part_id'), fn ($query) => $query->where('reportings.part_id', $request->integer('part_id')))
                ->when($request->filled('part_serial_number_id'), fn ($query) => $query->where('reportings.part_serial_number_id', $request->integer('part_serial_number_id')))
                ->when($request->filled('operation_id'), fn ($query) => $query->where('reportings.operation_id', $request->integer('operation_id')))
                ->when($request->filled('operation_id_actual'), fn ($query) => $query->where('reportings.operation_id_actual', $request->integer('operation_id_actual')))
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
                ->when($request->filled('group_id'), function ($query) use ($request) {
                    $query->whereIn('reportings.informant_id', function ($subQuery) use ($request) {
                        $subQuery->select('id')
                            ->from('informants')
                            ->where('group_id', $request->integer('group_id'));
                    });
                })
                ->whereBetween('reportings.reporting_date', [$periodStart->toDateTimeString(), $periodEnd->toDateTimeString()]);

            $summaryRows = (clone $baseQuery)
                ->select([
                    'reportings.reporting_date',
                    'reportings.processing_date_finish',
                    'reportings.processing_date_start',
                ])
                ->get();

            $totalFinishingSeconds = 0;
            $totalFromReportSeconds = 0;

            foreach ($summaryRows as $summaryRow) {
                if ($summaryRow->processing_date_start !== null && $summaryRow->processing_date_finish !== null) {
                    $finishTime = strtotime((string) $summaryRow->processing_date_finish);
                    $startTime = strtotime((string) $summaryRow->processing_date_start);

                    if ($finishTime !== false && $startTime !== false) {
                        $totalFinishingSeconds += max(0, $finishTime - $startTime);
                    }
                }

                if ($summaryRow->reporting_date !== null && $summaryRow->processing_date_finish !== null) {
                    $finishTime = strtotime((string) $summaryRow->processing_date_finish);
                    $reportingTime = strtotime((string) $summaryRow->reporting_date);

                    if ($finishTime !== false && $reportingTime !== false) {
                        $totalFromReportSeconds += max(0, $finishTime - $reportingTime);
                    }
                }
            }

            $downtimes = (clone $baseQuery)
                ->select([
                    'reportings.id',
                    'reportings.machine_id',
                    'reportings.position_id',
                    'reportings.part_id',
                    'reportings.reporting_number',
                    'reportings.reporting_date',
                    'reportings.reporting_notes',
                    'reportings.processing_date_start',
                    'reportings.processing_date_finish',
                    'reportings.reporting_type',
                    'reportings.status',
                    'reportings.notes',
                    'reportings.approved_notes',
                    'reportings.approved_at',
                    'divisions.name as division_name',
                    'shift_reporting.name as shift_reporting_name',
                    'shift_start.name as shift_start_name',
                    'shift_finish.name as shift_finish_name',
                    'machines.name as machine_name',
                    'positions.name as position_name',
                    'parts.name as part_name',
                    'reasons.name as reason_name',
                    'operations.name as operation_name',
                    'operations_act.name as operation_act_name',
                    'informants.name as informant_name',
                    'approved.name as approved_name',
                    'part_serial_numbers.serial_number as serial_number',
                    'groups.name as group_name',
                ])
                ->orderByDesc('reportings.id')
                ->paginate($perPage)
                ->appends($request->query());

            $items = collect($downtimes->items());
            $reportingIds = $items->pluck('id')->all();
            $technicianNamesByReportingId = [];

            if ($reportingIds !== []) {
                $technicianRows = DB::table('reporting_technician')
                    ->join('technicians', 'technicians.id', '=', 'reporting_technician.technician_id')
                    ->whereIn('reporting_technician.reporting_id', $reportingIds)
                    ->select('reporting_technician.reporting_id', 'technicians.name')
                    ->orderBy('technicians.name')
                    ->get()
                    ->groupBy('reporting_id');

                foreach ($technicianRows as $reportingId => $rows) {
                    $technicianNamesByReportingId[$reportingId] = $rows->pluck('name')->implode(', ');
                }
            }

            $data = $items->map(function ($row) use ($technicianNamesByReportingId) {
                $row->technician_names = $technicianNamesByReportingId[$row->id] ?? null;
                $finishTime = $row->processing_date_finish !== null ? strtotime((string) $row->processing_date_finish) : false;
                $startTime = $row->processing_date_start !== null ? strtotime((string) $row->processing_date_start) : false;
                $reportingTime = $row->reporting_date !== null ? strtotime((string) $row->reporting_date) : false;

                $row->total_time_finishing_seconds = $finishTime !== false && $startTime !== false
                    ? max(0, $finishTime - $startTime)
                    : 0;
                $row->total_time_from_report_seconds = $finishTime !== false && $reportingTime !== false
                    ? max(0, $finishTime - $reportingTime)
                    : 0;

                return DowntimeDataHelper::transform($row);
            })->all();

            return ApiResponseHelper::success('Data retrieved successfully', $data, [
                'current_page' => $downtimes->currentPage(),
                'last_page' => $downtimes->lastPage(),
                'per_page' => $downtimes->perPage(),
                'total' => $downtimes->total(),
                'summary' => [
                    'total_time_finishing' => DowntimeDataHelper::formatDuration($totalFinishingSeconds),
                    'total_time_finishing_minutes' => round($totalFinishingSeconds / 60, 2),
                    'total_time_from_report' => DowntimeDataHelper::formatDuration($totalFromReportSeconds),
                    'total_time_from_report_minutes' => round($totalFromReportSeconds / 60, 2),
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                ],
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve downtime data');
        }
    }
}
