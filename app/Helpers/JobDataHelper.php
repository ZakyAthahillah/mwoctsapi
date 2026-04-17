<?php

namespace App\Helpers;

class JobDataHelper
{
    public static function transform(object $row, array $technicianNames = []): array
    {
        return [
            'id' => (string) $row->id,
            'area_id' => $row->area_id !== null ? (string) $row->area_id : null,
            'reporting_number' => $row->reporting_number,
            'reporting_type' => $row->reporting_type !== null ? (int) $row->reporting_type : null,
            'reporting_type_name' => self::reportingTypeLabel($row->reporting_type),
            'status' => (int) $row->status,
            'status_name' => JobWorkflowHelper::statusLabel((int) $row->status),
            'reporting_date' => self::formatDateTime($row->reporting_date),
            'processing_date_start' => self::formatDateTime($row->processing_date_start),
            'processing_date_finish' => self::formatDateTime($row->processing_date_finish),
            'approved_at' => self::formatDateTime($row->approved_at),
            'gap_time_response' => $row->gap_time_response,
            'total_time_finishing' => $row->total_time_finishing,
            'total_time_approved' => $row->total_time_approved,
            'division_id' => $row->division_id !== null ? (string) $row->division_id : null,
            'division_name' => $row->division_name,
            'machine_id' => $row->machine_id !== null ? (string) $row->machine_id : null,
            'machine_name' => $row->machine_name,
            'position_id' => $row->position_id !== null ? (string) $row->position_id : null,
            'position_name' => $row->position_name,
            'part_id' => $row->part_id !== null ? (string) $row->part_id : null,
            'part_name' => $row->part_name,
            'part_serial_number_id' => $row->part_serial_number_id !== null ? (string) $row->part_serial_number_id : null,
            'part_serial_number_new_id' => $row->part_serial_number_id_new !== null ? (string) $row->part_serial_number_id_new : null,
            'serial_number' => $row->serial_number,
            'operation_id' => $row->operation_id !== null ? (string) $row->operation_id : null,
            'operation_name' => $row->operation_name,
            'operation_id_actual' => $row->operation_id_actual !== null ? (string) $row->operation_id_actual : null,
            'operation_actual_name' => $row->operation_actual_name,
            'reason_id' => $row->reason_id !== null ? (string) $row->reason_id : null,
            'reason_name' => $row->reason_name,
            'informant_id' => $row->informant_id !== null ? (string) $row->informant_id : null,
            'informant_name' => $row->informant_name,
            'approved_by' => $row->approved_by !== null ? (string) $row->approved_by : null,
            'approved_name' => $row->approved_name,
            'group_name' => $row->group_name,
            'shift_id_reporting' => $row->shift_id_reporting !== null ? (string) $row->shift_id_reporting : null,
            'shift_reporting_name' => $row->shift_reporting_name,
            'shift_id_start' => $row->shift_id_start !== null ? (string) $row->shift_id_start : null,
            'shift_start_name' => $row->shift_start_name,
            'shift_id_finish' => $row->shift_id_finish !== null ? (string) $row->shift_id_finish : null,
            'shift_finish_name' => $row->shift_finish_name,
            'shift_id_approved' => $row->shift_id_approved !== null ? (string) $row->shift_id_approved : null,
            'shift_approved_name' => $row->shift_approved_name,
            'reporting_notes' => $row->reporting_notes,
            'notes' => $row->notes,
            'approved_notes' => $row->approved_notes,
            'technician_names' => array_values($technicianNames),
            'created_at' => self::formatDateTime($row->created_at ?? null),
            'updated_at' => self::formatDateTime($row->updated_at ?? null),
        ];
    }

    public static function reportingTypeLabel(mixed $reportingType): ?string
    {
        return match ((int) $reportingType) {
            1 => 'mechanical',
            2 => 'electrical',
            default => null,
        };
    }

    public static function formatDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
