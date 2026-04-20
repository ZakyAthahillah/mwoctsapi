<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\MetricDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MtbfIndexRequest;
use App\Http\Requests\Api\MtbfTaskplusRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MtbfController extends Controller
{
    public function index(MtbfIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $user?->area_id;

            $type = (string) $request->input('type');
            $year = Carbon::now('Asia/Jakarta')->year;
            $yearMin = $year - 5;
            $yearReq = $request->integer('year');
            $monthReq = $request->integer('month');
            $isTaskplus = $request->boolean('is_taskplus');

            $reportingsQuery = DB::table('reportings')
                ->select([
                    'id',
                    'area_id',
                    'machine_id',
                    'position_id',
                    'part_id',
                    'part_serial_number_id',
                    'reporting_date',
                    'processing_date_start',
                    'processing_date_finish',
                    'shift_id_start',
                ])
                ->where('status', 5)
                ->where('reporting_type', 1)
                ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
                ->when($request->filled('machine_id') && ! $isTaskplus, fn ($query) => $query->where('machine_id', $request->integer('machine_id')))
                ->when($request->filled('position_id') && ! $isTaskplus, fn ($query) => $query->where('position_id', $request->integer('position_id')))
                ->when($request->filled('part_id') && ! $isTaskplus, fn ($query) => $query->where('part_id', $request->integer('part_id')))
                ->when($request->filled('part_serial_number_id') && ! $isTaskplus, fn ($query) => $query->where('part_serial_number_id', $request->integer('part_serial_number_id')));

            if ($type === 'yearly') {
                $reportingsQuery->whereYear('reporting_date', '>=', $yearMin);
            } elseif ($type === 'monthly') {
                $reportingsQuery->whereYear('reporting_date', $yearReq);
            } elseif ($type === 'daily') {
                $reportingsQuery->whereYear('reporting_date', $yearReq)->whereMonth('reporting_date', $monthReq);
            } else {
                $reportingsQuery->whereBetween('reporting_date', [
                    Carbon::parse((string) $request->input('period_start'))->startOfDay()->toDateTimeString(),
                    Carbon::parse((string) $request->input('period_end'))->endOfDay()->toDateTimeString(),
                ]);
            }

            $reportings = $reportingsQuery->get();
            $bucketCounts = [];
            $bucketDowntimeSeconds = [];

            foreach ($reportings as $reporting) {
                $bucketKey = MetricDataHelper::bucketKey($type, $reporting, $yearReq, $monthReq);
                if ($bucketKey === null) {
                    continue;
                }

                $bucketCounts[$bucketKey] = ($bucketCounts[$bucketKey] ?? 0) + 1;

                $start = $reporting->processing_date_start ? strtotime((string) $reporting->processing_date_start) : false;
                $finish = $reporting->processing_date_finish ? strtotime((string) $reporting->processing_date_finish) : false;

                if ($start !== false && $finish !== false) {
                    $bucketDowntimeSeconds[$bucketKey] = ($bucketDowntimeSeconds[$bucketKey] ?? 0) + max(0, $finish - $start);
                }
            }

            $buckets = MetricDataHelper::buildMtbfBuckets($type, $yearMin, $year, $yearReq, $monthReq, $request);
            $shiftNames = $type === 'shift'
                ? DB::table('shifts')
                    ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                    ->when($areaId === null, fn ($query) => $query->whereNull('area_id'))
                    ->pluck('name', 'id')
                    ->all()
                : [];

            $targetMap = MetricDataHelper::buildMtbfTargetMap($type, $areaId, $yearMin, $year, $yearReq, $monthReq, $request, $buckets, $shiftNames);

            $taskplusDailyTarget = [];
            $taskplusComparators = null;
            if ($type === 'daily' && $isTaskplus) {
                [$targetMap, $taskplusDailyTarget, $taskplusComparators] = MetricDataHelper::buildTaskplusDailyTarget(
                    $areaId,
                    $yearReq,
                    $monthReq,
                    $buckets,
                    $bucketDowntimeSeconds
                );
            }

            $data = [];
            foreach ($buckets as $bucketKey => $bucketMeta) {
                $total = $bucketCounts[$bucketKey] ?? 0;
                $downtimeHours = round(($bucketDowntimeSeconds[$bucketKey] ?? 0) / 3600, 2);

                if ($type === 'daily' && $isTaskplus) {
                    $value = $taskplusDailyTarget[$bucketKey] ?? 0;
                } else {
                    $availableHours = $bucketMeta['hours'];
                    $productionHours = max(0, $availableHours - $downtimeHours);
                    $value = $total > 0 ? $productionHours / $total : 0;
                }

                $payload = [
                    'date' => $bucketMeta['label'],
                    'mtbf' => number_format($value, 2, '.', ''),
                    'target' => number_format((float) ($targetMap[$bucketKey] ?? 0), 2, '.', ''),
                    'downtime' => $value,
                    'total' => $total,
                ];

                if ($type === 'daily' && $isTaskplus) {
                    $payload['pembanding'] = $taskplusComparators;
                    $payload['targetMtc'] = (float) ($targetMap[$bucketKey] ?? 0);
                }

                $data[] = $payload;
            }

            return ApiResponseHelper::success('Data retrieved successfully', $data);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve MTBF data');
        }
    }

    public function taskplus(MtbfTaskplusRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $user?->area_id;

            $yearReq = $request->integer('year');

            $reportingCountsRows = DB::table('reportings')
                ->where('status', 5)
                ->where('reporting_type', 1)
                ->whereYear('reporting_date', $yearReq)
                ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
                ->get()
                ->groupBy(function ($row) {
                    return Carbon::parse((string) $row->reporting_date)->month;
                });

            $reportingCounts = [];
            foreach ($reportingCountsRows as $month => $rows) {
                $reportingCounts[(int) $month] = $rows->count();
            }

            $tatTpd = MetricDataHelper::buildMonthlyTatTpdMap($areaId, $yearReq);

            $data = [];
            for ($month = 1; $month <= 12; $month++) {
                $tat = isset($tatTpd[$month]) ? (float) $tatTpd[$month]['total_tat'] : 0.0;
                $tpd = isset($tatTpd[$month]) ? (float) $tatTpd[$month]['total_tpd'] : 0.0;
                $total = (int) ($reportingCounts[$month] ?? 0);
                $mtbf = $total > 0 ? (($tat - $tpd) / $total) : 0;

                $data[] = [
                    'date' => Carbon::createFromDate($yearReq, $month, 1)->format('M-Y'),
                    'mtbf' => number_format($mtbf, 2, '.', ''),
                    'tat' => $tat,
                    'tpd' => $tpd,
                ];
            }

            return ApiResponseHelper::success('Data retrieved successfully', $data);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve MTBF taskplus data');
        }
    }
}
