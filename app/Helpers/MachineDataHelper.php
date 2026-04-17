<?php

namespace App\Helpers;

use App\Models\Machine;

class MachineDataHelper
{
    public static function transform(Machine $machine): array
    {
        return [
            'id' => (string) $machine->id,
            'area_id' => $machine->area_id !== null ? (string) $machine->area_id : null,
            'area_name' => $machine->area?->name,
            'code' => $machine->code,
            'name' => $machine->name,
            'description' => $machine->description,
            'image' => $machine->image,
            'image_side' => $machine->image_side,
            'status' => (int) $machine->status,
            'created_at' => optional($machine->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($machine->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
