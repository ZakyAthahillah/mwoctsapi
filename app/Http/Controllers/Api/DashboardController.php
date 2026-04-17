<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\DashboardDataHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $request->query('area_id');

            if (($areaId === null || $areaId === '') && $user?->area_id !== null) {
                $areaId = $user->area_id;
            }

            $counts = DB::table('reportings')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->groupBy('status')
                ->pluck('total', 'status');

            $reporting = [
                'new' => (int) ($counts[1] ?? 0),
                'on_progress' => (int) ($counts[2] ?? 0),
                'extend' => (int) ($counts[3] ?? 0),
                'approval' => (int) ($counts[4] ?? 0),
                'finish' => (int) ($counts[5] ?? 0),
            ];

            $periodStart = Carbon::now('Asia/Jakarta')->subDays(30);
            $periodEnd = Carbon::now('Asia/Jakarta');

            return ApiResponseHelper::success('Data retrieved successfully', DashboardDataHelper::transform($reporting, $periodStart, $periodEnd));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve dashboard data');
        }
    }
}
