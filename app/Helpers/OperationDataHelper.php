<?php

namespace App\Helpers;

use App\Models\Operation;

class OperationDataHelper
{
    public static function transform(Operation $operation): array
    {
        return [
            'id' => (string) $operation->id,
            'area_id' => $operation->area_id !== null ? (string) $operation->area_id : null,
            'area_name' => $operation->area?->name,
            'code' => $operation->code,
            'name' => $operation->name,
            'status' => (int) $operation->status,
            'created_at' => optional($operation->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($operation->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
