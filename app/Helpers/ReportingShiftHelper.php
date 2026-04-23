<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingShiftHelper
{
    public static function resolve(?int $areaId, string $dateTime): ?object
    {
        $reportingDate = Carbon::parse($dateTime);
        $time = $reportingDate->format('H:i:s');

        return DB::table('shifts')
            ->where('status', 1)
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
            ->where(function ($query) use ($time) {
                $query->where(function ($normalShiftQuery) use ($time) {
                    $normalShiftQuery->whereColumn('time_start', '<=', 'time_finish')
                        ->where('time_start', '<=', $time)
                        ->where('time_finish', '>=', $time);
                })->orWhere(function ($overnightShiftQuery) use ($time) {
                    $overnightShiftQuery->whereColumn('time_start', '>', 'time_finish')
                        ->where(function ($timeQuery) use ($time) {
                            $timeQuery->where('time_start', '<=', $time)
                                ->orWhere('time_finish', '>=', $time);
                        });
                });
            })
            ->orderBy('id')
            ->first();
    }

    public static function transform(?object $shift, string $dateTime): array
    {
        return [
            'time' => Carbon::parse($dateTime)->format('Y-m-d H:i:s'),
            'shift_id' => $shift?->id !== null ? (string) $shift->id : null,
            'shift_name' => $shift?->name,
            'time_start' => $shift?->time_start,
            'time_finish' => $shift?->time_finish,
        ];
    }
}
