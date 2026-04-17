<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\FbdtDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckFbdtRequest;
use App\Http\Requests\Api\StoreFbdtRequest;
use App\Http\Requests\Api\UpdateFbdtRequest;
use App\Models\Fbdt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FbdtController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $areaId = $request->query('area_id');

            if (($areaId === null || $areaId === '') && $user?->area_id !== null) {
                $areaId = $user->area_id;
            }

            $query = Fbdt::query()
                ->leftJoin('areas', 'areas.id', '=', 'fbdts.area_id')
                ->select('fbdts.tahun', 'fbdts.area_id', 'areas.name as area_name', DB::raw('COUNT(*) as months_count'))
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->when($request->filled('year'), fn ($builder) => $builder->where('tahun', $request->integer('year')))
                ->groupBy('fbdts.tahun', 'fbdts.area_id', 'areas.name')
                ->orderByDesc('fbdts.tahun');

            $fbdts = $query->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', collect($fbdts->items())
                ->map(fn ($row) => FbdtDataHelper::transformYearSummary($row))
                ->all(), [
                    'current_page' => $fbdts->currentPage(),
                    'last_page' => $fbdts->lastPage(),
                    'per_page' => $fbdts->perPage(),
                    'total' => $fbdts->total(),
                ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve FBDT data');
        }
    }

    public function store(StoreFbdtRequest $request)
    {
        try {
            $areaId = $request->integer('area_id');
            $year = $request->integer('year');

            DB::transaction(function () use ($request, $areaId, $year) {
                Fbdt::query()
                    ->where('area_id', $areaId)
                    ->where('tahun', $year)
                    ->delete();

                Fbdt::insert(collect($request->input('targets'))
                    ->map(fn ($target) => [
                        'area_id' => $areaId,
                        'tahun' => $year,
                        'bulan' => (int) $target['month'],
                        'fb' => $target['fb'],
                        'dt' => $target['dt'],
                        'mtbf' => $target['mtbf'],
                        'mttr' => $target['mttr'],
                    ])->all());
            });

            $rows = Fbdt::query()
                ->with('area')
                ->where('area_id', $areaId)
                ->where('tahun', $year)
                ->orderBy('bulan')
                ->get();

            return ApiResponseHelper::success('FBDT created successfully', FbdtDataHelper::transformYearDetail($rows), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create FBDT data');
        }
    }

    public function show(Request $request, int $year)
    {
        try {
            $user = auth('api')->user();
            $areaId = $request->query('area_id');

            if (($areaId === null || $areaId === '') && $user?->area_id !== null) {
                $areaId = $user->area_id;
            }

            $rows = Fbdt::query()
                ->with('area')
                ->when($areaId !== null && $areaId !== '', fn ($builder) => $builder->where('area_id', $areaId))
                ->where('tahun', $year)
                ->orderBy('bulan')
                ->get();

            if ($rows->isEmpty()) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', FbdtDataHelper::transformYearDetail($rows));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve FBDT detail');
        }
    }

    public function update(UpdateFbdtRequest $request, int $year)
    {
        try {
            $areaId = $request->integer('area_id');

            DB::transaction(function () use ($request, $areaId, $year) {
                Fbdt::query()
                    ->where('area_id', $areaId)
                    ->where('tahun', $year)
                    ->delete();

                Fbdt::insert(collect($request->input('targets'))
                    ->map(fn ($target) => [
                        'area_id' => $areaId,
                        'tahun' => $year,
                        'bulan' => (int) $target['month'],
                        'fb' => $target['fb'],
                        'dt' => $target['dt'],
                        'mtbf' => $target['mtbf'],
                        'mttr' => $target['mttr'],
                    ])->all());
            });

            $rows = Fbdt::query()
                ->with('area')
                ->where('area_id', $areaId)
                ->where('tahun', $year)
                ->orderBy('bulan')
                ->get();

            return ApiResponseHelper::success('FBDT updated successfully', FbdtDataHelper::transformYearDetail($rows));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update FBDT data');
        }
    }

    public function check(CheckFbdtRequest $request)
    {
        try {
            $exists = Fbdt::query()
                ->where('area_id', $request->integer('area_id'))
                ->where('tahun', $request->integer('year'))
                ->exists();

            return ApiResponseHelper::success('Data retrieved successfully', [
                'exists' => $exists,
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to check FBDT data');
        }
    }
}
