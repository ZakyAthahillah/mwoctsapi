<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class MonitorQueryHelper
{
    public static function baseQuery(?int $areaId)
    {
        return DB::table('reportings')
            ->leftJoin('divisions', 'divisions.id', '=', 'reportings.division_id')
            ->leftJoin('shifts as shift_reporting', 'shift_reporting.id', '=', 'reportings.shift_id_reporting')
            ->leftJoin('machines', 'machines.id', '=', 'reportings.machine_id')
            ->leftJoin('positions', 'positions.id', '=', 'reportings.position_id')
            ->leftJoin('parts', 'parts.id', '=', 'reportings.part_id')
            ->leftJoin('operations', 'operations.id', '=', 'reportings.operation_id')
            ->leftJoin('reasons', 'reasons.id', '=', 'reportings.reason_id')
            ->leftJoin('informants', 'informants.id', '=', 'reportings.informant_id')
            ->leftJoin('groups', 'groups.id', '=', 'informants.group_id')
            ->leftJoin('part_serial_numbers', 'part_serial_numbers.id', '=', 'reportings.part_serial_number_id')
            ->when($areaId !== null, fn ($query) => $query->where('reportings.area_id', $areaId), fn ($query) => $query->whereNull('reportings.area_id'))
            ->select([
                'reportings.id',
                'reportings.area_id',
                'reportings.machine_id',
                'reportings.position_id',
                'reportings.part_id',
                'reportings.reporting_number',
                'reportings.reporting_date',
                'reportings.reporting_type',
                'reportings.division_id',
                'reportings.shift_id_reporting',
                'reportings.operation_id',
                'reportings.reason_id',
                'reportings.reporting_notes',
                'reportings.informant_id',
                'reportings.processing_date_start',
                'reportings.processing_date_finish',
                'reportings.total_time_finishing',
                'reportings.status',
                'reportings.notes',
                'reportings.approved_at',
                'reportings.approved_notes',
                'reportings.part_serial_number_id',
                'divisions.name as division_name',
                'shift_reporting.name as shift_reporting_name',
                'machines.name as machine_name',
                'positions.name as position_name',
                'parts.name as part_name',
                'operations.name as operation_name',
                'reasons.name as reason_name',
                'informants.name as informant_name',
                'groups.name as group_name',
                'part_serial_numbers.serial_number as serial_number',
            ]);
    }

    public static function technicianNamesByReportingIds(array $reportingIds): array
    {
        if ($reportingIds === [] || ! DB::getSchemaBuilder()->hasTable('reporting_technician')) {
            return [];
        }

        $technicianRows = DB::table('reporting_technician')
            ->join('technicians', 'technicians.id', '=', 'reporting_technician.technician_id')
            ->whereIn('reporting_technician.reporting_id', $reportingIds)
            ->orderBy('technicians.name')
            ->get([
                'reporting_technician.reporting_id',
                'technicians.name',
            ])
            ->groupBy('reporting_id');

        $technicianNames = [];

        foreach ($technicianRows as $reportingId => $rows) {
            $technicianNames[$reportingId] = $rows->pluck('name')->all();
        }

        return $technicianNames;
    }
}
