<?php

namespace App\Helpers;

class ReportingReportDataHelper
{
    public static function transform(object $row, array $technicianNames = []): array
    {
        $totalTimeFinishingSeconds = self::durationToSeconds($row->total_time_finishing ?? null);

        return [
            'id' => (string) $row->id,
            'reporting_number' => $row->reporting_number,
            'reporting_type' => $row->reporting_type !== null ? (int) $row->reporting_type : null,
            'reporting_type_name' => ReportingDataHelper::reportingTypeLabel($row->reporting_type),
            'reporting_date' => ReportingDataHelper::formatDateTime($row->reporting_date),
            'reporting_time' => self::formatTime($row->reporting_date),
            'division_id' => $row->division_id !== null ? (string) $row->division_id : null,
            'division_name' => $row->division_name,
            'shift_id_reporting' => $row->shift_id_reporting !== null ? (string) $row->shift_id_reporting : null,
            'shift_reporting_name' => $row->shift_reporting_name,
            'machine_id' => $row->machine_id !== null ? (string) $row->machine_id : null,
            'machine_name' => $row->machine_name,
            'position_id' => $row->position_id !== null ? (string) $row->position_id : null,
            'position_name' => $row->position_name,
            'part_id' => $row->part_id !== null ? (string) $row->part_id : null,
            'part_name' => $row->part_name,
            'part_serial_number_id' => $row->part_serial_number_id !== null ? (string) $row->part_serial_number_id : null,
            'serial_number' => $row->serial_number,
            'operation_id' => $row->operation_id !== null ? (string) $row->operation_id : null,
            'operation_name' => $row->operation_name,
            'operation_id_actual' => $row->operation_id_actual !== null ? (string) $row->operation_id_actual : null,
            'operation_actual_name' => $row->operation_actual_name,
            'reason_id' => $row->reason_id !== null ? (string) $row->reason_id : null,
            'reason_name' => $row->reason_name,
            'informant_id' => $row->informant_id !== null ? (string) $row->informant_id : null,
            'informant_name' => $row->informant_name,
            'group_name' => $row->group_name,
            'technician_names' => $technicianNames,
            'reporting_notes' => $row->reporting_notes,
            'processing_date_start' => ReportingDataHelper::formatDateTime($row->processing_date_start),
            'processing_date_finish' => ReportingDataHelper::formatDateTime($row->processing_date_finish),
            'total_time_finishing' => self::formatDuration($totalTimeFinishingSeconds),
            'total_time_finishing_minutes' => round($totalTimeFinishingSeconds / 60, 2),
            'approved_at' => ReportingDataHelper::formatDateTime($row->approved_at),
            'notes' => $row->notes,
            'status' => (int) $row->status,
            'status_name' => ReportingDataHelper::statusLabel((int) $row->status),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            ['id' => 1, 'text' => 'new'],
            ['id' => 2, 'text' => 'on_progress'],
            ['id' => 3, 'text' => 'extend'],
            ['id' => 4, 'text' => 'waiting_for_approval'],
            ['id' => 5, 'text' => 'finish'],
            ['id' => 99, 'text' => 'deleted'],
        ];
    }

    public static function durationToSeconds(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $parts = array_map('intval', explode(':', (string) $value));

        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        return 0;
    }

    public static function formatDuration(int $seconds): ?string
    {
        return $seconds > 0 ? gmdate('H:i:s', $seconds) : null;
    }

    private static function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('H:i:s', $timestamp);
    }
}
