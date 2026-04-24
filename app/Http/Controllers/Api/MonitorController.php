<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\MonitorDataHelper;
use App\Helpers\MonitorQueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MonitorIndexRequest;
use Carbon\Carbon;

class MonitorController extends Controller
{
    public function index(MonitorIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $user?->area_id;
            $page = max(1, (int) $request->integer('page', 1));
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $periodEnd = $request->input('period_end')
                ? Carbon::parse((string) $request->input('period_end'))->endOfDay()
                : Carbon::now('Asia/Jakarta')->endOfDay();
            $periodStart = $request->input('period_start')
                ? Carbon::parse((string) $request->input('period_start'))->startOfDay()
                : (clone $periodEnd)->subDay()->startOfDay();

            if ($periodStart->gt($periodEnd)) {
                return ApiResponseHelper::error('Bad request', [
                    'period_start' => ['The period start must be before or equal to period end.'],
                ], 400);
            }

            $previousPeriodStart = (clone $periodStart)->subDays(3)->startOfDay();
            $previousPeriodEnd = (clone $periodStart)->subDay()->endOfDay();

            $recentRows = MonitorQueryHelper::baseQuery($areaId)
                ->where('reportings.status', '<>', 99)
                ->whereBetween('reportings.reporting_date', [$periodStart->toDateTimeString(), $periodEnd->toDateTimeString()])
                ->get();

            $previousRows = MonitorQueryHelper::baseQuery($areaId)
                ->whereBetween('reportings.reporting_date', [$previousPeriodStart->toDateTimeString(), $previousPeriodEnd->toDateTimeString()])
                ->where(function ($query) {
                    $query->where('reportings.status', '<=', 2)
                        ->orWhere('reportings.status', 4);
                })
                ->get();

            $rows = $recentRows->merge($previousRows)
                ->sortBy([
                    ['reporting_date', 'desc'],
                    ['status', 'asc'],
                ])
                ->values();
            $paginatedRows = $rows->forPage($page, $perPage)->values();

            $technicianNames = MonitorQueryHelper::technicianNamesByReportingIds($paginatedRows->pluck('id')->all());

            return ApiResponseHelper::success('Data retrieved successfully', $paginatedRows->map(
                fn ($row) => MonitorDataHelper::transform($row, $technicianNames[$row->id] ?? [])
            )->all(), [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($rows->count() / $perPage)),
                'per_page' => $perPage,
                'total' => $rows->count(),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'previous_period_start' => $previousPeriodStart->format('Y-m-d'),
                'previous_period_end' => $previousPeriodEnd->format('Y-m-d'),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve monitor data');
        }
    }
}
