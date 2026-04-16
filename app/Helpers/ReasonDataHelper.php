<?php

namespace App\Helpers;

use App\Models\Reason;

class ReasonDataHelper
{
    public static function transform(Reason $reason): array
    {
        return [
            'id' => (string) $reason->id,
            'area_id' => $reason->area_id !== null ? (string) $reason->area_id : null,
            'code' => $reason->code,
            'name' => $reason->name,
            'division_id' => $reason->division_id !== null ? (string) $reason->division_id : null,
            'status' => (int) $reason->status,
            'created_at' => optional($reason->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($reason->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
