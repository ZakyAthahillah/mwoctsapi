<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\MetricDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MttrIndexRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MttrController extends Controller
{
    public function index(MttrIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $request->input('area_id');
            if (($areaId === null || $areaId === '') && $user?->area_id !== null) {
                $areaId = $user->area_id;
            }

            $type = (string) $request->input('type');
            $year = Carbon::now('Asia/Jakarta')->year;
            $yearMin = $year - 5;
            $yearReq = $request->integer('year');
            $monthReq = $request->integer('month');

            $reportingsQuery = DB::table('reportings')
                ->select([
                    'id',
                    'area_id',
                    'machine_id',
                    'position_id',
                    'part_id',
                    'operation_id',
                    'reporting_date',
                    'processing_date_start',
                    'processing_date_finish',
                    'shift_id_start',
                ])
                ->where('status', 5)
                ->where('reporting_type', 1)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($request->filled('machine_id'), fn ($query) => $query->where('machine_id', $request->integer('machine_id')))
                ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
                ->when($request->filled('part_id'), fn ($query) => $query->where('part_id', $request->integer('part_id')))
                ->when($request->filled('operation_id'), fn ($query) => $query->where('operation_id', $request->integer('operation_id')));

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
            $bucketRepairSeconds = [];

            foreach ($reportings as $reporting) {
                $bucketKey = MetricDataHelper::bucketKey($type, $reporting, $yearReq, $monthReq);
                if ($bucketKey === null) {
                    continue;
                }

                $bucketCounts[$bucketKey] = ($bucketCounts[$bucketKey] ?? 0) + 1;

                $start = $reporting->processing_date_start ? strtotime((string) $reporting->processing_date_start) : false;
                $finish = $reporting->processing_date_finish ? strtotime((string) $reporting->processing_date_finish) : false;

                if ($start !== false && $finish !== false) {
                    $bucketRepairSeconds[$bucketKey] = ($bucketRepairSeconds[$bucketKey] ?? 0) + max(0, $finish - $start);
                }
            }

            $buckets = MetricDataHelper::buildMttrBuckets($type, $yearMin, $year, $yearReq, $monthReq, $request);
            $shiftNames = $type === 'shift'
                ? DB::table('shifts')
                    ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                    ->pluck('name', 'id')
                    ->all()
                : [];
            $targetMap = MetricDataHelper::buildMttrTargetMap($type, $areaId, $yearMin, $year, $yearReq, $monthReq, $request, $buckets, $shiftNames);

            $data = [];
            foreach ($buckets as $bucketKey => $bucketMeta) {
                $total = $bucketCounts[$bucketKey] ?? 0;
                $repairMinutes = ($bucketRepairSeconds[$bucketKey] ?? 0) / 60;
                $value = $total > 0 ? ($repairMinutes / $total) : 0;

                $data[] = [
                    'date' => $bucketMeta['label'],
                    'mttr' => number_format($value, 2, '.', ''),
                    'target' => number_format((float) ($targetMap[$bucketKey] ?? 0), 2, '.', ''),
                    'downtime' => $value,
                    'total' => $total,
                ];
            }

            return ApiResponseHelper::success('Data retrieved successfully', $data);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve MTTR data');
        }
    }
}
