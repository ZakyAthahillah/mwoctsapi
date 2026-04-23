<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class ReportingQueryHelper
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
            ->leftJoin('reasons', 'reasons.id', '=', 'reportings.reason_id')
            ->leftJoin('informants', 'informants.id', '=', 'reportings.informant_id')
            ->leftJoin('groups', 'groups.id', '=', 'informants.group_id')
            ->leftJoin('part_serial_numbers', 'part_serial_numbers.id', '=', 'reportings.part_serial_number_id')
            ->select([
                'reportings.*',
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

    public static function findForArea(int $reportingId, ?int $areaId, bool $includeDeleted = false): ?object
    {
        return self::baseQuery()
            ->where('reportings.id', $reportingId)
            ->when(! $includeDeleted, fn ($query) => $query->where('reportings.status', '<>', 99))
            ->when($areaId !== null, fn ($query) => $query->where('reportings.area_id', $areaId), fn ($query) => $query->whereNull('reportings.area_id'))
            ->first();
    }

    public static function nextSortOrder(?int $areaId): int
    {
        $lastSortOrder = DB::table('reportings')
            ->where('status', 1)
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
            ->whereNotNull('sort_order')
            ->max('sort_order');

        return ((int) $lastSortOrder) + 1;
    }
}
