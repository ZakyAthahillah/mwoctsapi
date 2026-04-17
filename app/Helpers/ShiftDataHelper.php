<?php

namespace App\Helpers;

use App\Models\Shift;

class ShiftDataHelper
{
    public static function transform(Shift $shift): array
    {
        return [
            'id' => (string) $shift->id,
            'area_id' => $shift->area_id !== null ? (string) $shift->area_id : null,
            'area_name' => $shift->area?->name,
            'name' => $shift->name,
            'time_start' => $shift->time_start !== null ? date('H:i:s', strtotime((string) $shift->time_start)) : null,
            'time_finish' => $shift->time_finish !== null ? date('H:i:s', strtotime((string) $shift->time_finish)) : null,
            'status' => (int) $shift->status,
            'created_at' => optional($shift->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($shift->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
