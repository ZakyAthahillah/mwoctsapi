<?php

namespace App\Helpers;

use App\Models\Position;

class PositionDataHelper
{
    public static function transform(Position $position): array
    {
        return [
            'id' => (string) $position->id,
            'area_id' => $position->area_id !== null ? (string) $position->area_id : null,
            'name' => $position->name,
            'description' => $position->description,
            'status' => (int) $position->status,
            'created_at' => optional($position->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($position->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
