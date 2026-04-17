<?php

namespace App\Helpers;

class DowntimeDataHelper
{
    public static function transform(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'reporting_number' => $row->reporting_number,
            'reporting_date' => $row->reporting_date !== null ? date('Y-m-d H:i:s', strtotime((string) $row->reporting_date)) : null,
            'division_name' => $row->division_name,
            'shift_reporting_name' => $row->shift_reporting_name,
            'shift_start_name' => $row->shift_start_name,
            'shift_finish_name' => $row->shift_finish_name,
            'machine_id' => $row->machine_id !== null ? (string) $row->machine_id : null,
            'machine_name' => $row->machine_name,
            'position_id' => $row->position_id !== null ? (string) $row->position_id : null,
            'position_name' => $row->position_name,
            'part_id' => $row->part_id !== null ? (string) $row->part_id : null,
            'part_name' => $row->part_name,
            'serial_number' => $row->serial_number,
            'operation_name' => $row->operation_name,
            'operation_actual_name' => $row->operation_act_name,
            'reason_name' => $row->reason_name,
            'informant_name' => $row->informant_name,
            'group_name' => $row->group_name,
            'reporting_type' => $row->reporting_type,
            'reporting_notes' => $row->reporting_notes,
            'processing_date_start' => $row->processing_date_start !== null ? date('Y-m-d H:i:s', strtotime((string) $row->processing_date_start)) : null,
            'processing_date_finish' => $row->processing_date_finish !== null ? date('Y-m-d H:i:s', strtotime((string) $row->processing_date_finish)) : null,
            'approved_at' => $row->approved_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->approved_at)) : null,
            'approved_name' => $row->approved_name,
            'notes' => $row->notes,
            'approved_notes' => $row->approved_notes,
            'status' => (int) $row->status,
            'technician_names' => $row->technician_names !== null && $row->technician_names !== '' ? explode(', ', (string) $row->technician_names) : [],
            'total_time_finishing_seconds' => (int) $row->total_time_finishing_seconds,
            'total_time_finishing_minutes' => round(((int) $row->total_time_finishing_seconds) / 60, 2),
            'total_time_from_report_seconds' => (int) $row->total_time_from_report_seconds,
            'total_time_from_report_minutes' => round(((int) $row->total_time_from_report_seconds) / 60, 2),
        ];
    }

    public static function formatDuration(int $seconds): string
    {
        return gmdate('H:i:s', max(0, $seconds));
    }
}
