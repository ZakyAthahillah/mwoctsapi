<?php

namespace App\Helpers;

use App\Models\Operation;

class OperationDataHelper
{
    public static function transform(Operation $operation): array
    {
        $payload = [
            'id' => (string) $operation->id,
            'area_id' => $operation->area_id !== null ? (string) $operation->area_id : null,
            'area_name' => $operation->area?->name,
            'code' => $operation->code,
            'name' => $operation->name,
            'status' => (int) $operation->status,
            'created_at' => optional($operation->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($operation->updated_at)?->format('Y-m-d H:i:s'),
        ];

        if ($operation->relationLoaded('divisions')) {
            $payload['division_id'] = $operation->divisions
                ->map(fn ($division) => (string) $division->id)
                ->values()
                ->all();
            $payload['division_name'] = $operation->divisions
                ->map(fn ($division) => $division->name)
                ->values()
                ->all();
        }

        if ($operation->relationLoaded('parts')) {
            $payload['part_id'] = $operation->parts
                ->map(fn ($part) => (string) $part->id)
                ->values()
                ->all();
            $payload['part_name'] = $operation->parts
                ->map(fn ($part) => $part->name)
                ->values()
                ->all();
        }

        return $payload;
    }
}
