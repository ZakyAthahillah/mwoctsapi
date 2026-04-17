<?php

namespace App\Helpers;

use App\Models\Part;

class PartDataHelper
{
    public static function transform(Part $part): array
    {
        return [
            'id' => (string) $part->id,
            'area_id' => $part->area_id !== null ? (string) $part->area_id : null,
            'area_name' => $part->area?->name,
            'code' => $part->code,
            'name' => $part->name,
            'description' => $part->description,
            'status' => (int) $part->status,
            'created_at' => optional($part->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($part->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
