<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\PartDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePartRequest;
use App\Http\Requests\Api\UpdatePartRequest;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $partsQuery = Part::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $parts = $partsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $parts->getCollection()->map(
                fn (Part $part) => PartDataHelper::transform($part)
            )->all(), [
                'current_page' => $parts->currentPage(),
                'last_page' => $parts->lastPage(),
                'per_page' => $parts->perPage(),
                'total' => $parts->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve parts');
        }
    }

    public function store(StorePartRequest $request)
    {
        try {
            $part = DB::transaction(function () use ($request) {
                return Part::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ]);
            });

            $part->load('area');

            return ApiResponseHelper::success('Part created successfully', PartDataHelper::transform($part), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create part');
        }
    }

    public function show(Part $part)
    {
        try {
            if ((int) $part->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $part->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', PartDataHelper::transform($part));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve part');
        }
    }

    public function update(UpdatePartRequest $request, Part $part)
    {
        try {
            if ((int) $part->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Part has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $part) {
                $part->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ]);
            });

            $part->refresh()->load('area');

            return ApiResponseHelper::success('Part updated successfully', PartDataHelper::transform($part));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update part');
        }
    }

    public function destroy(Part $part)
    {
        try {
            if ((int) $part->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Part has already been deleted.'],
                ], 400);
            }

            $part->update([
                'status' => 99,
            ]);

            $part->refresh()->load('area');

            return ApiResponseHelper::success('Part deleted successfully', PartDataHelper::transform($part));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete part');
        }
    }
}
