<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\AreaDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAreaRequest;
use App\Http\Requests\Api\UpdateAreaRequest;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $areasQuery = Area::query()
                ->where('status', '<>', 99)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%')
                            ->orWhere('object_name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $areas = $areasQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $areas->getCollection()->map(
                fn (Area $area) => AreaDataHelper::transform($area)
            )->all(), [
                'current_page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
                'per_page' => $areas->perPage(),
                'total' => $areas->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve areas');
        }
    }

    public function store(StoreAreaRequest $request)
    {
        try {
            $area = DB::transaction(function () use ($request) {
                return Area::create([
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'object_name' => $request->string('object_name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            return ApiResponseHelper::success('Area created successfully', AreaDataHelper::transform($area), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create area');
        }
    }

    public function show(Area $area)
    {
        try {
            if ((int) $area->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', AreaDataHelper::transform($area));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve area');
        }
    }

    public function update(UpdateAreaRequest $request, Area $area)
    {
        try {
            if ((int) $area->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Area has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $area) {
                $area->update([
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'object_name' => $request->string('object_name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $area->refresh();

            return ApiResponseHelper::success('Area updated successfully', AreaDataHelper::transform($area));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update area');
        }
    }

    public function destroy(Area $area)
    {
        try {
            if ((int) $area->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Area has already been deleted.'],
                ], 400);
            }

            $area->update([
                'status' => 99,
            ]);

            return ApiResponseHelper::success('Area deleted successfully', AreaDataHelper::transform($area));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete area');
        }
    }
}
