<?php

namespace App\Helpers;

use App\Models\Area;

class AreaDataHelper
{
    public static function transform(Area $area): array
    {
        return [
            'id' => (string) $area->id,
            'code' => $area->code,
            'name' => $area->name,
            'object_name' => $area->object_name,
            'status' => (int) $area->status,
            'created_at' => optional($area->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($area->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
