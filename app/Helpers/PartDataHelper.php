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
            'total_operation' => (int) ($part->operations_count ?? 0),
            'total_reason' => (int) ($part->reasons_count ?? 0),
            'total_serial_number' => (int) ($part->serial_numbers_count ?? 0),
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

    public static function selectDataArray(iterable $parts): array
    {
        $items = [];

        foreach ($parts as $part) {
            $items[] = [
                'id' => (string) $part->id,
                'text' => $part->name,
            ];
        }

        return $items;
    }

    public static function fullDataArray(iterable $parts): array
    {
        $items = [];

        foreach ($parts as $part) {
            $items[] = [
                'id' => (string) $part->id,
                'text' => trim(($part->code ?? '').' : '.($part->name ?? '')),
                'code' => $part->code,
                'name' => $part->name,
                'description' => $part->description,
            ];
        }

        return $items;
    }

    public static function operationOptions(iterable $operations): array
    {
        $items = [];

        foreach ($operations as $operation) {
            $items[] = [
                'id' => (string) $operation->id,
                'text' => trim(($operation->code ?? '').' : '.($operation->name ?? '')),
            ];
        }

        return $items;
    }
}
