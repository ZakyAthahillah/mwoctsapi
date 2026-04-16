<?php

namespace App\Helpers;

use App\Models\Technician;

class TechnicianDataHelper
{
    public static function transform(Technician $technician): array
    {
        return [
            'id' => (string) $technician->id,
            'area_id' => $technician->area_id !== null ? (string) $technician->area_id : null,
            'code' => $technician->code,
            'name' => $technician->name,
            'division_id' => $technician->division_id !== null ? (string) $technician->division_id : null,
            'status' => (int) $technician->status,
            'group_id' => $technician->group_id !== null ? (string) $technician->group_id : null,
            'created_at' => optional($technician->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($technician->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
