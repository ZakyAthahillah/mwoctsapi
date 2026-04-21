<?php

namespace App\Helpers;

use App\Models\Part;

class PartDataHelper
{
    public static function transform(Part $part): array
    {
        $payload = [
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

        if ($part->relationLoaded('operations')) {
            $payload['operation_id'] = $part->operations
                ->map(fn ($operation) => (string) $operation->id)
                ->values()
                ->all();
            $payload['operation_name'] = $part->operations
                ->map(fn ($operation) => $operation->name)
                ->values()
                ->all();
        }

        if ($part->relationLoaded('reasons')) {
            $payload['reason_id'] = $part->reasons
                ->map(fn ($reason) => (string) $reason->id)
                ->values()
                ->all();
            $payload['reason_name'] = $part->reasons
                ->map(fn ($reason) => $reason->name)
                ->values()
                ->all();
        }

        return $payload;
    }
}
