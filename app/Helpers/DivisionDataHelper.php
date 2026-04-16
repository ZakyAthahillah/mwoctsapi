<?php

namespace App\Helpers;

use App\Models\Division;

class DivisionDataHelper
{
    public static function transform(Division $division): array
    {
        return [
            'id' => (string) $division->id,
            'area_id' => $division->area_id !== null ? (string) $division->area_id : null,
            'code' => $division->code,
            'name' => $division->name,
            'status' => (int) $division->status,
            'created_at' => optional($division->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($division->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
