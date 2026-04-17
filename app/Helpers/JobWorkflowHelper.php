<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JobWorkflowHelper
{
    public static function statusMap(): array
    {
        return [
            'new' => 1,
            'on_progress' => 2,
            'onProgress' => 2,
            'extend' => 3,
            'waiting_for_approval' => 4,
            'approval' => 4,
            'finish' => 5,
        ];
    }

    public static function statusLabel(int $status): string
    {
        return match ($status) {
            1 => 'new',
            2 => 'on_progress',
            3 => 'extend',
            4 => 'waiting_for_approval',
            5 => 'finish',
            default => 'unknown',
        };
    }

    public static function statusCodeFromFilter(?string $status): ?int
    {
        if ($status === null || $status === '') {
            return null;
        }

        $map = self::statusMap();

        return $map[$status] ?? null;
    }

    public static function resolveShiftId(?int $areaId, string $dateTime): ?int
    {
        if ($areaId === null || $dateTime === '') {
            return null;
        }

        $targetTime = Carbon::parse($dateTime)->format('H:i:s');
        $shifts = DB::table('shifts')
            ->select('id', 'time_start', 'time_finish')
            ->where('area_id', $areaId)
            ->where('status', 1)
            ->orderBy('time_start')
            ->get();

        foreach ($shifts as $shift) {
            $start = self::normalizeTime($shift->time_start);
            $finish = self::normalizeTime($shift->time_finish);

            if ($start === null || $finish === null) {
                continue;
            }

            if ($start <= $finish && $targetTime >= $start && $targetTime <= $finish) {
                return (int) $shift->id;
            }

            if ($start > $finish && ($targetTime >= $start || $targetTime <= $finish)) {
                return (int) $shift->id;
            }
        }

        return null;
    }

    public static function syncTechnicians(string $table, string $foreignKey, int $recordId, array $technicianIds): void
    {
        DB::table($table)->where($foreignKey, $recordId)->delete();

        if ($technicianIds === []) {
            return;
        }

        DB::table($table)->insert(array_map(
            fn (int $technicianId) => [
                $foreignKey => $recordId,
                'technician_id' => $technicianId,
            ],
            $technicianIds
        ));
    }

    public static function uniqueTechnicianIds(array $technicianIds): array
    {
        return array_values(array_unique(array_map('intval', $technicianIds)));
    }

    private static function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('H:i:s', $timestamp);
    }
}
