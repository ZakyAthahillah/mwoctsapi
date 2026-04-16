<?php

namespace App\Helpers;

use App\Models\PartSerialNumber;
use App\Models\SerialNumber;
use App\Models\SerialNumberLog;

class SerialNumberDataHelper
{
    public static function transform(SerialNumber $serialNumber): array
    {
        return [
            'id' => (string) $serialNumber->id,
            'area_id' => $serialNumber->area_id !== null ? (string) $serialNumber->area_id : null,
            'machine_id' => $serialNumber->machine_id !== null ? (string) $serialNumber->machine_id : null,
            'machine_name' => $serialNumber->machine?->name,
            'position_id' => $serialNumber->position_id !== null ? (string) $serialNumber->position_id : null,
            'position_name' => $serialNumber->position?->name,
            'part_id' => $serialNumber->part_id !== null ? (string) $serialNumber->part_id : null,
            'part_name' => $serialNumber->part?->name,
            'part_serial_number_id' => $serialNumber->part_serial_number_id !== null ? (string) $serialNumber->part_serial_number_id : null,
            'serial_number' => $serialNumber->partSerialNumber?->serial_number,
        ];
    }

    public static function transformFirst(PartSerialNumber $partSerialNumber, ?SerialNumberLog $log): array
    {
        return [
            'part_serial_number_id' => (string) $partSerialNumber->id,
            'area_id' => $partSerialNumber->area_id !== null ? (string) $partSerialNumber->area_id : null,
            'part_id' => $partSerialNumber->part_id !== null ? (string) $partSerialNumber->part_id : null,
            'part_name' => $partSerialNumber->part?->name,
            'serial_number' => $partSerialNumber->serial_number,
            'status' => (int) $partSerialNumber->status,
            'first_assignment' => $log !== null ? [
                'log_id' => (string) $log->id,
                'machine_id' => $log->machine_id !== null ? (string) $log->machine_id : null,
                'machine_name' => $log->machine?->name,
                'position_id' => $log->position_id !== null ? (string) $log->position_id : null,
                'position_name' => $log->position?->name,
                'action' => (int) $log->action,
                'updated_by' => $log->updatedBy !== null ? (string) $log->updatedBy : null,
                'updated_at' => optional($log->updatedDate)?->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }
}
