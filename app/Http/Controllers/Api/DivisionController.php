<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\DivisionDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDivisionRequest;
use App\Http\Requests\Api\UpdateDivisionRequest;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $divisionsQuery = Division::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $divisions = $divisionsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $divisions->getCollection()->map(
                fn (Division $division) => DivisionDataHelper::transform($division)
            )->all(), [
                'current_page' => $divisions->currentPage(),
                'last_page' => $divisions->lastPage(),
                'per_page' => $divisions->perPage(),
                'total' => $divisions->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve divisions');
        }
    }

    public function store(StoreDivisionRequest $request)
    {
        try {
            $division = DB::transaction(function () use ($request) {
                return Division::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $division->load('area');

            return ApiResponseHelper::success('Division created successfully', DivisionDataHelper::transform($division), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create division');
        }
    }

    public function show(Division $division)
    {
        try {
            if ((int) $division->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $division->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', DivisionDataHelper::transform($division));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve division');
        }
    }

    public function update(UpdateDivisionRequest $request, Division $division)
    {
        try {
            if ((int) $division->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Division has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $division) {
                $division->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $division->refresh()->load('area');

            return ApiResponseHelper::success('Division updated successfully', DivisionDataHelper::transform($division));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update division');
        }
    }

    public function destroy(Division $division)
    {
        try {
            if ((int) $division->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Division has already been deleted.'],
                ], 400);
            }

            $division->update([
                'status' => 99,
            ]);

            $division->refresh()->load('area');

            return ApiResponseHelper::success('Division deleted successfully', DivisionDataHelper::transform($division));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete division');
        }
    }
}
