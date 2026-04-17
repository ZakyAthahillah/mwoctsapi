<?php

namespace App\Helpers;

use App\Http\Requests\Api\MtbfIndexRequest;
use App\Http\Requests\Api\MttrIndexRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class MetricDataHelper
{
    public static function bucketKey(string $type, object $reporting, int $yearReq, int $monthReq): ?string
    {
        $reportingDate = $reporting->reporting_date ? Carbon::parse((string) $reporting->reporting_date) : null;
        if (! $reportingDate) {
            return null;
        }

        return match ($type) {
            'yearly' => $reportingDate->format('Y'),
            'monthly' => $reportingDate->format('n'),
            'daily' => $reportingDate->year === $yearReq && $reportingDate->month === $monthReq ? $reportingDate->format('j') : null,
            'shift' => $reporting->shift_id_start !== null ? $reportingDate->format('Y-m-d').'|'.$reporting->shift_id_start : null,
            default => null,
        };
    }

    public static function buildMtbfBuckets(string $type, int $yearMin, int $year, int $yearReq, int $monthReq, MtbfIndexRequest $request): array
    {
        $buckets = [];

        if ($type === 'yearly') {
            for ($current = $yearMin; $current <= $year; $current++) {
                $days = Carbon::create($current, 1, 1)->isLeapYear() ? 366 : 365;
                $buckets[(string) $current] = [
                    'label' => (string) $current,
                    'hours' => 24 * $days,
                ];
            }

            return $buckets;
        }

        if ($type === 'monthly') {
            for ($month = 1; $month <= 12; $month++) {
                $days = Carbon::create($yearReq, $month, 1)->daysInMonth;
                $buckets[(string) $month] = [
                    'label' => Carbon::create($yearReq, $month, 1)->format('M-Y'),
                    'hours' => 24 * $days,
                ];
            }

            return $buckets;
        }

        if ($type === 'daily') {
            $days = Carbon::create($yearReq, $monthReq, 1)->daysInMonth;
            for ($day = 1; $day <= $days; $day++) {
                $buckets[(string) $day] = [
                    'label' => Carbon::create($yearReq, $monthReq, $day)->format('d/m/Y'),
                    'hours' => 24,
                ];
            }

            return $buckets;
        }

        $period = CarbonPeriod::create(
            Carbon::parse((string) $request->input('period_start'))->startOfDay(),
            Carbon::parse((string) $request->input('period_end'))->startOfDay()
        );

        $shiftNames = DB::table('shifts')->pluck('name', 'id')->all();
        foreach ($period as $date) {
            foreach ($shiftNames as $shiftId => $shiftName) {
                $buckets[$date->format('Y-m-d').'|'.$shiftId] = [
                    'label' => $date->format('d/m/y').' - '.$shiftName,
                    'hours' => 24,
                ];
            }
        }

        return $buckets;
    }

    public static function buildMttrBuckets(string $type, int $yearMin, int $year, int $yearReq, int $monthReq, MttrIndexRequest $request): array
    {
        $buckets = [];

        if ($type === 'yearly') {
            for ($current = $yearMin; $current <= $year; $current++) {
                $buckets[(string) $current] = [
                    'label' => (string) $current,
                ];
            }

            return $buckets;
        }

        if ($type === 'monthly') {
            for ($month = 1; $month <= 12; $month++) {
                $buckets[(string) $month] = [
                    'label' => Carbon::create($yearReq, $month, 1)->format('M-Y'),
                ];
            }

            return $buckets;
        }

        if ($type === 'daily') {
            $days = Carbon::create($yearReq, $monthReq, 1)->daysInMonth;
            for ($day = 1; $day <= $days; $day++) {
                $buckets[(string) $day] = [
                    'label' => Carbon::create($yearReq, $monthReq, $day)->format('d/m/Y'),
                ];
            }

            return $buckets;
        }

        $period = CarbonPeriod::create(
            Carbon::parse((string) $request->input('period_start'))->startOfDay(),
            Carbon::parse((string) $request->input('period_end'))->startOfDay()
        );

        $shiftNames = DB::table('shifts')->pluck('name', 'id')->all();
        foreach ($period as $date) {
            foreach ($shiftNames as $shiftId => $shiftName) {
                $buckets[$date->format('Y-m-d').'|'.$shiftId] = [
                    'label' => $date->format('d/m/y').' - '.$shiftName,
                ];
            }
        }

        return $buckets;
    }

    public static function buildMtbfTargetMap(
        string $type,
        mixed $areaId,
        int $yearMin,
        int $year,
        int $yearReq,
        int $monthReq,
        MtbfIndexRequest $request,
        array $buckets,
        array $shiftNames
    ): array {
        $targetMap = [];
        $partId = $request->integer('part_id');
        $machineId = $request->integer('machine_id');

        if ($type === 'yearly') {
            $rows = DB::table('fbdts')
                ->selectRaw('tahun, SUM(mtbf) as total')
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->where('tahun', '>=', $yearMin)
                ->groupBy('tahun')
                ->pluck('total', 'tahun')
                ->all();

            for ($current = $yearMin; $current <= $year; $current++) {
                $targetMap[(string) $current] = (float) ($rows[$current] ?? 0);
            }

            return $targetMap;
        }

        if ($type === 'monthly') {
            $table = (! empty($machineId) && ! empty($partId)) ? 'target_models' : 'fbdts';
            $column = 'mtbf';
            $query = DB::table($table)
                ->select('bulan', $column)
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $yearReq);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $rows = $query->pluck($column, 'bulan')->all();

            foreach (array_keys($buckets) as $bucketKey) {
                $targetMap[$bucketKey] = (float) ($rows[(int) $bucketKey] ?? 0);
            }

            return $targetMap;
        }

        if ($type === 'daily') {
            $table = (! empty($machineId) && ! empty($partId)) ? 'target_models' : 'fbdts';
            $column = 'mtbf';
            $query = DB::table($table)
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $yearReq)
                ->where('bulan', $monthReq);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $row = $query->first([$column]);
            $days = count($buckets);
            $value = $row ? ((float) $row->$column / max(1, $days)) : 0;

            foreach (array_keys($buckets) as $bucketKey) {
                $targetMap[$bucketKey] = $value;
            }

            return $targetMap;
        }

        $partId = $request->integer('part_id');
        $table = (! empty($machineId) && ! empty($partId)) ? 'target_models' : 'fbdts';
        foreach ($buckets as $bucketKey => $bucketMeta) {
            [$date, $shiftId] = explode('|', $bucketKey);
            $dateValue = Carbon::parse($date);

            $query = DB::table($table)
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $dateValue->year)
                ->where('bulan', $dateValue->month);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $row = $query->first(['mtbf']);
            $targetMap[$bucketKey] = $row ? ((float) $row->mtbf / max(1, count($shiftNames))) : 0;
        }

        return $targetMap;
    }

    public static function buildTaskplusDailyTarget(mixed $areaId, int $yearReq, int $monthReq, array $buckets, array $bucketDowntimeSeconds): array
    {
        $tatTpdRows = DB::table('tat_tpds')
            ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
            ->whereYear('tanggal', $yearReq)
            ->whereMonth('tanggal', $monthReq)
            ->get()
            ->mapWithKeys(function ($row) {
                return [Carbon::parse((string) $row->tanggal)->day => $row];
            });

        $fbdt = DB::table('fbdts')
            ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
            ->where('tahun', $yearReq)
            ->where('bulan', $monthReq)
            ->first(['dt', 'mtbf']);

        $targetMap = [];
        $mtbfMap = [];
        $comparators = [
            'MtcDowntimeMWO' => [],
            'DT' => [],
            'MTBF' => [],
            'TargetDowntimeHours' => [],
            'TotalAvailTime' => [],
            'TaskPlusDowntime' => [],
            'TargetDowntimePercent' => [],
            'DowntimeMTC' => [],
            'prodDowntime' => [],
            'DowntimeProd' => [],
        ];

        foreach (array_keys($buckets) as $bucketKey) {
            $day = (int) $bucketKey;
            $tat = isset($tatTpdRows[$day]) ? (float) $tatTpdRows[$day]->tat : 0;
            $tpd = isset($tatTpdRows[$day]) ? (float) $tatTpdRows[$day]->tpd : 0;
            $dt = $fbdt ? (float) $fbdt->dt : 0;
            $mtbf = $fbdt ? (float) $fbdt->mtbf : 0;
            $mtcDowntimeHours = round(($bucketDowntimeSeconds[$bucketKey] ?? 0) / 3600, 2);
            $prodDowntime = $tpd - $mtcDowntimeHours;

            $targetPercent = ($tat > 0 && $mtbf > 0) ? (($dt / ($tat * $mtbf)) * 100) : 0;
            $mtcPercent = $tat > 0 ? (($mtcDowntimeHours / $tat) * 100) : 0;
            $prodPercent = $tat > 0 ? (($prodDowntime / $tat) * 100) : 0;

            $targetMap[$bucketKey] = $mtcPercent;
            $mtbfMap[$bucketKey] = $prodPercent;

            $comparators['MtcDowntimeMWO'][$day] = $mtcDowntimeHours;
            $comparators['DT'][$day] = $dt;
            $comparators['MTBF'][$day] = $mtbf;
            $comparators['TargetDowntimeHours'][$day] = $mtbf > 0 ? ($dt / $mtbf) : 0;
            $comparators['TotalAvailTime'][$day] = $tat;
            $comparators['TaskPlusDowntime'][$day] = $tpd;
            $comparators['TargetDowntimePercent'][$day] = $targetPercent;
            $comparators['DowntimeMTC'][$day] = $mtcPercent;
            $comparators['prodDowntime'][$day] = $prodDowntime;
            $comparators['DowntimeProd'][$day] = $prodPercent;
        }

        return [$targetMap, $mtbfMap, $comparators];
    }

    public static function buildMonthlyTatTpdMap(mixed $areaId, int $yearReq): array
    {
        $rows = DB::table('tat_tpds')
            ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
            ->whereYear('tanggal', $yearReq)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $month = Carbon::parse((string) $row->tanggal)->month;
            $grouped[$month]['tat'][] = (float) ($row->tat ?? 0);
            $grouped[$month]['tpd'][] = (float) ($row->tpd ?? 0);
        }

        $result = [];
        foreach ($grouped as $month => $values) {
            $result[$month] = [
                'total_tat' => count($values['tat']) > 0 ? array_sum($values['tat']) / count($values['tat']) : 0,
                'total_tpd' => count($values['tpd']) > 0 ? array_sum($values['tpd']) / count($values['tpd']) : 0,
            ];
        }

        return $result;
    }

    public static function buildMttrTargetMap(
        string $type,
        mixed $areaId,
        int $yearMin,
        int $year,
        int $yearReq,
        int $monthReq,
        MttrIndexRequest $request,
        array $buckets,
        array $shiftNames
    ): array {
        $targetMap = [];
        $partId = $request->integer('part_id');
        $machineId = $request->integer('machine_id');
        $operationId = $request->integer('operation_id');

        if ($type === 'yearly') {
            $rows = DB::table('fbdts')
                ->selectRaw('tahun, SUM(mttr) as total')
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->where('tahun', '>=', $yearMin)
                ->groupBy('tahun')
                ->pluck('total', 'tahun')
                ->all();

            for ($current = $yearMin; $current <= $year; $current++) {
                $targetMap[(string) $current] = (float) ($rows[$current] ?? 0);
            }

            return $targetMap;
        }

        if ($type === 'monthly') {
            $table = (! empty($machineId) && ! empty($partId) && empty($operationId)) ? 'target_models' : 'fbdts';
            $query = DB::table($table)
                ->select('bulan', 'mttr')
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $yearReq);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $rows = $query->pluck('mttr', 'bulan')->all();

            foreach (array_keys($buckets) as $bucketKey) {
                $targetMap[$bucketKey] = (float) ($rows[(int) $bucketKey] ?? 0);
            }

            return $targetMap;
        }

        if ($type === 'daily') {
            $table = (! empty($machineId) && ! empty($partId) && empty($operationId)) ? 'target_models' : 'fbdts';
            $query = DB::table($table)
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $yearReq)
                ->where('bulan', $monthReq);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $row = $query->first(['mttr']);
            $days = count($buckets);
            $value = $row ? ((float) $row->mttr / max(1, $days)) : 0;

            foreach (array_keys($buckets) as $bucketKey) {
                $targetMap[$bucketKey] = $value;
            }

            return $targetMap;
        }

        $table = (! empty($machineId) && ! empty($partId) && empty($operationId)) ? 'target_models' : 'fbdts';
        foreach ($buckets as $bucketKey => $bucketMeta) {
            [$date, $shiftId] = explode('|', $bucketKey);
            $dateValue = Carbon::parse($date);

            $query = DB::table($table)
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $dateValue->year)
                ->where('bulan', $dateValue->month);

            if ($table === 'target_models') {
                $query->where('part_id', $partId);
            }

            $row = $query->first(['mttr']);
            $targetMap[$bucketKey] = $row ? ((float) $row->mttr / max(1, count($shiftNames))) : 0;
        }

        return $targetMap;
    }
}
