<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingReportQueryHelper
{
    public static function baseQuery()
    {
        return DB::table('reportings')
            ->leftJoin('divisions', 'divisions.id', '=', 'reportings.division_id')
            ->leftJoin('shifts as shift_reporting', 'shift_reporting.id', '=', 'reportings.shift_id_reporting')
            ->leftJoin('machines', 'machines.id', '=', 'reportings.machine_id')
            ->leftJoin('positions', 'positions.id', '=', 'reportings.position_id')
            ->leftJoin('parts', 'parts.id', '=', 'reportings.part_id')
            ->leftJoin('operations', 'operations.id', '=', 'reportings.operation_id')
            ->leftJoin('operations as operations_act', 'operations_act.id', '=', 'reportings.operation_id_actual')
            ->leftJoin('reasons', 'reasons.id', '=', 'reportings.reason_id')
            ->leftJoin('informants', 'informants.id', '=', 'reportings.informant_id')
            ->leftJoin('groups', 'groups.id', '=', 'informants.group_id')
            ->leftJoin('part_serial_numbers', 'part_serial_numbers.id', '=', 'reportings.part_serial_number_id')
            ->select([
                'reportings.id',
                'reportings.machine_id',
                'reportings.position_id',
                'reportings.part_id',
                'reportings.reporting_number',
                'reportings.reporting_date',
                'reportings.division_id',
                'reportings.shift_id_reporting',
                'reportings.operation_id',
                'reportings.operation_id_actual',
                'reportings.reason_id',
                'reportings.reporting_notes',
                'reportings.informant_id',
                'reportings.processing_date_start',
                'reportings.processing_date_finish',
                'reportings.total_time_finishing',
                'reportings.status',
                'reportings.notes',
                'reportings.reporting_type',
                'reportings.approved_at',
                'reportings.part_serial_number_id',
                'divisions.name as division_name',
                'shift_reporting.name as shift_reporting_name',
                'machines.name as machine_name',
                'positions.name as position_name',
                'parts.name as part_name',
                'operations.name as operation_name',
                'operations_act.name as operation_actual_name',
                'reasons.name as reason_name',
                'informants.name as informant_name',
                'groups.name as group_name',
                'part_serial_numbers.serial_number as serial_number',
            ]);
    }

    public static function applyFilters($query, Request $request, ?int $areaId)
    {
        $periodStart = $request->input('period_start')
            ? Carbon::parse((string) $request->input('period_start'))->startOfDay()
            : Carbon::now('Asia/Jakarta')->subDays(30)->startOfDay();
        $periodEnd = $request->input('period_end')
            ? Carbon::parse((string) $request->input('period_end'))->endOfDay()
            : Carbon::now('Asia/Jakarta')->endOfDay();

        return $query
            ->where('reportings.status', '<>', 99)
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
            ->when($request->filled('status'), fn ($query) => $query->where('reportings.status', $request->integer('status')))
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
            ->whereBetween('reportings.reporting_date', [$periodStart->toDateTimeString(), $periodEnd->toDateTimeString()]);
    }

    public static function technicianNamesByReportingIds(array $reportingIds): array
    {
        if ($reportingIds === [] || ! DB::getSchemaBuilder()->hasTable('reporting_technician')) {
            return [];
        }

        $rows = DB::table('reporting_technician')
            ->join('technicians', 'technicians.id', '=', 'reporting_technician.technician_id')
            ->whereIn('reporting_technician.reporting_id', $reportingIds)
            ->select('reporting_technician.reporting_id', 'technicians.name')
            ->orderBy('technicians.name')
            ->get()
            ->groupBy('reporting_id');

        $technicians = [];

        foreach ($rows as $reportingId => $items) {
            $technicians[$reportingId] = $items->pluck('name')->all();
        }

        return $technicians;
    }
}
