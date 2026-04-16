<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\PositionDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePositionRequest;
use App\Http\Requests\Api\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $positionsQuery = Position::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $positions = $positionsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $positions->getCollection()->map(
                fn (Position $position) => PositionDataHelper::transform($position)
            )->all(), [
                'current_page' => $positions->currentPage(),
                'last_page' => $positions->lastPage(),
                'per_page' => $positions->perPage(),
                'total' => $positions->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve positions');
        }
    }

    public function store(StorePositionRequest $request)
    {
        try {
            $position = DB::transaction(function () use ($request) {
                return Position::create([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ]);
            });

            $position->load('area');

            return ApiResponseHelper::success('Position created successfully', PositionDataHelper::transform($position), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create position');
        }
    }

    public function show(Position $position)
    {
        try {
            if ((int) $position->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $position->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', PositionDataHelper::transform($position));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve position');
        }
    }

    public function update(UpdatePositionRequest $request, Position $position)
    {
        try {
            if ((int) $position->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Position has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $position) {
                $position->update([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ]);
            });

            $position->refresh()->load('area');

            return ApiResponseHelper::success('Position updated successfully', PositionDataHelper::transform($position));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update position');
        }
    }

    public function destroy(Position $position)
    {
        try {
            if ((int) $position->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Position has already been deleted.'],
                ], 400);
            }

            $position->update([
                'status' => 99,
            ]);

            $position->refresh()->load('area');

            return ApiResponseHelper::success('Position deleted successfully', PositionDataHelper::transform($position));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete position');
        }
    }
}
