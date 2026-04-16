<?php

namespace App\Helpers;

use App\Models\Informant;

class InformantDataHelper
{
    public static function transform(Informant $informant): array
    {
        return [
            'id' => (string) $informant->id,
            'area_id' => $informant->area_id !== null ? (string) $informant->area_id : null,
            'code' => $informant->code,
            'name' => $informant->name,
            'status' => (int) $informant->status,
            'group_id' => $informant->group_id !== null ? (string) $informant->group_id : null,
            'created_at' => optional($informant->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($informant->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
