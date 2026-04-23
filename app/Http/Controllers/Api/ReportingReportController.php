<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\ReportingReportDataHelper;
use App\Helpers\ReportingReportQueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportingReportIndexRequest;

class ReportingReportController extends Controller
{
    public function index(ReportingReportIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $reportingReports = ReportingReportQueryHelper::applyFilters(
                ReportingReportQueryHelper::baseQuery(),
                $request,
                $user?->area_id
            )
                ->orderByDesc('reportings.id')
                ->paginate($perPage)
                ->appends($request->query());

            $items = collect($reportingReports->items());
            $technicianNames = ReportingReportQueryHelper::technicianNamesByReportingIds($items->pluck('id')->all());

            return ApiResponseHelper::success('Data retrieved successfully', $items->map(
                fn ($row) => ReportingReportDataHelper::transform($row, $technicianNames[$row->id] ?? [])
            )->all(), [
                'current_page' => $reportingReports->currentPage(),
                'last_page' => $reportingReports->lastPage(),
                'per_page' => $reportingReports->perPage(),
                'total' => $reportingReports->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reporting report data');
        }
    }

    public function statuses()
    {
        try {
            return ApiResponseHelper::success('Data retrieved successfully', ReportingReportDataHelper::statusOptions());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reporting report statuses');
        }
    }
}
