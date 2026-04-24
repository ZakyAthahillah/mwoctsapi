<?php

namespace App\Helpers;

class MonitorDataHelper
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
            'status_name' => self::statusLabel((int) $row->status),
            'status_text' => strtoupper(self::statusLabel((int) $row->status)),
            'status_class' => self::statusClass((int) $row->status),
            'reporting_date' => self::formatDateTime($row->reporting_date),
            'reporting_time' => self::formatTime($row->reporting_date),
            'processing_date_start' => self::formatDateTime($row->processing_date_start),
            'processing_start_time' => self::formatTime($row->processing_date_start),
            'processing_date_finish' => self::formatDateTime($row->processing_date_finish),
            'processing_finish_time' => self::formatTime($row->processing_date_finish),
            'approved_at' => self::formatDateTime($row->approved_at),
            'approved_time' => self::formatTime($row->approved_at),
            'total_time_finishing' => self::formatTime($row->total_time_finishing),
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
            'reason_id' => $row->reason_id !== null ? (string) $row->reason_id : null,
            'reason_name' => $row->reason_name,
            'informant_id' => $row->informant_id !== null ? (string) $row->informant_id : null,
            'informant_name' => $row->informant_name,
            'group_name' => $row->group_name,
            'technician_names' => array_values($technicianNames),
            'reporting_notes' => $row->reporting_notes,
            'notes' => $row->notes,
            'approved_notes' => $row->approved_notes,
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

    public static function statusLabel(int $status): string
    {
        return match ($status) {
            1 => 'new',
            2 => 'on_progress',
            3 => 'extend',
            4 => 'waiting_for_approval',
            5 => 'finish',
            99 => 'deleted',
            default => 'unknown',
        };
    }

    public static function statusClass(int $status): string
    {
        return match ($status) {
            1 => 'primary',
            2 => 'warning',
            3 => 'danger',
            4 => 'info',
            5 => 'success',
            99 => 'secondary',
            default => 'secondary',
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

    public static function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('H:i:s', $timestamp);
    }
}
