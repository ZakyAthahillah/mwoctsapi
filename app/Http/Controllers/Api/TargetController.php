<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\TargetDataHelper;
use App\Helpers\TargetQueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckTargetRequest;
use App\Http\Requests\Api\StoreTargetRequest;
use App\Http\Requests\Api\TargetIndexRequest;
use App\Http\Requests\Api\UpdateTargetRequest;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    public function index(TargetIndexRequest $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));

            $targets = TargetQueryHelper::summaryQuery($user?->area_id)
                ->when($request->filled('year'), fn ($query) => $query->where('target_models.tahun', $request->integer('year')))
                ->when($request->filled('part_id'), fn ($query) => $query->where('target_models.part_id', $request->integer('part_id')))
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->input('search'));
                    $query->where('parts.name', 'like', '%'.$search.'%');
                })
                ->orderByDesc('target_models.tahun')
                ->orderBy('parts.name')
                ->paginate($perPage)
                ->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', collect($targets->items())->map(
                fn ($row) => TargetDataHelper::transformSummary($row)
            )->all(), [
                'current_page' => $targets->currentPage(),
                'last_page' => $targets->lastPage(),
                'per_page' => $targets->perPage(),
                'total' => $targets->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve targets');
        }
    }

    public function show(int $part, int $year)
    {
        try {
            $areaId = auth('api')->user()?->area_id;
            $summary = TargetQueryHelper::summary($areaId, $part, $year);

            if (! $summary) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', TargetDataHelper::transformDetail(
                $summary,
                TargetQueryHelper::months($areaId, $part, $year)
            ));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve target');
        }
    }

    public function store(StoreTargetRequest $request)
    {
        try {
            $user = auth('api')->user();
            $areaId = $user?->area_id;
            $partId = $request->integer('part_id');
            $year = $request->integer('year');

            DB::transaction(fn () => TargetQueryHelper::replaceMonths(
                $areaId,
                $partId,
                $year,
                $request->input('targets', [])
            ));

            return ApiResponseHelper::success('Target created successfully', TargetDataHelper::transformDetail(
                TargetQueryHelper::summary($areaId, $partId, $year),
                TargetQueryHelper::months($areaId, $partId, $year)
            ), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create target');
        }
    }

    public function update(UpdateTargetRequest $request, int $part, int $year)
    {
        try {
            $areaId = auth('api')->user()?->area_id;
            $existing = TargetQueryHelper::summary($areaId, $part, $year);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            DB::transaction(fn () => TargetQueryHelper::replaceMonths(
                $areaId,
                $part,
                $year,
                $request->input('targets', [])
            ));

            return ApiResponseHelper::success('Target updated successfully', TargetDataHelper::transformDetail(
                TargetQueryHelper::summary($areaId, $part, $year),
                TargetQueryHelper::months($areaId, $part, $year)
            ));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update target');
        }
    }

    public function destroy(int $part, int $year)
    {
        try {
            $areaId = auth('api')->user()?->area_id;
            $existing = TargetQueryHelper::summary($areaId, $part, $year);

            if (! $existing) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $data = TargetDataHelper::transformDetail($existing, TargetQueryHelper::months($areaId, $part, $year));

            DB::table('target_models')
                ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
                ->where('part_id', $part)
                ->where('tahun', $year)
                ->delete();

            return ApiResponseHelper::success('Target deleted successfully', $data);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete target');
        }
    }

    public function check(CheckTargetRequest $request)
    {
        try {
            $areaId = auth('api')->user()?->area_id;
            $exists = TargetQueryHelper::summary($areaId, $request->integer('part_id'), $request->integer('year')) !== null;

            return ApiResponseHelper::success('Data retrieved successfully', [
                'exists' => $exists,
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to check target');
        }
    }
}
