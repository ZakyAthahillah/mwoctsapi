<?php

namespace App\Helpers;

use App\Models\Reason;

class ReasonDataHelper
{
    public static function transform(Reason $reason): array
    {
        $payload = [
            'id' => (string) $reason->id,
            'area_id' => $reason->area_id !== null ? (string) $reason->area_id : null,
            'area_name' => $reason->area?->name,
            'code' => $reason->code,
            'name' => $reason->name,
            'division_id' => $reason->division_id !== null ? (string) $reason->division_id : null,
            'division_name' => $reason->division?->name,
            'status' => (int) $reason->status,
            'total_division' => (int) ($reason->divisions_count ?? 0),
            'total_part' => (int) ($reason->parts_count ?? 0),
            'created_at' => optional($reason->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($reason->updated_at)?->format('Y-m-d H:i:s'),
        ];

        if ($reason->relationLoaded('divisions')) {
            $payload['division_id'] = $reason->divisions
                ->map(fn ($division) => (string) $division->id)
                ->values()
                ->all();
            $payload['division_name'] = $reason->divisions
                ->map(fn ($division) => $division->name)
                ->values()
                ->all();
        }

        if ($reason->relationLoaded('parts')) {
            $payload['part_id'] = $reason->parts
                ->map(fn ($part) => (string) $part->id)
                ->values()
                ->all();
            $payload['part_name'] = $reason->parts
                ->map(fn ($part) => $part->name)
                ->values()
                ->all();
        }

        return $payload;
    }
}
