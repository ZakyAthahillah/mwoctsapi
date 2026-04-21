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
    public function partActive(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $partsQuery = Part::query()
                ->with('area')
                ->where('status', '<>', 11)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
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
            return ApiResponseHelper::error('Failed to retrieve active parts');
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $partsQuery = Part::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
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
                $part = Part::create([
                    'area_id' => auth('api')->user()?->area_id,
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ]);

                $part->operations()->sync($request->validated('operation_id') ?? []);
                $part->reasons()->sync($request->validated('reason_id') ?? []);

                return $part;
            });

            $part->load(['area', 'operations', 'reasons']);

            return ApiResponseHelper::success('Part created successfully', PartDataHelper::transform($part), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create part');
        }
    }

    public function show(Part $part)
    {
        try {
            $user = auth('api')->user();
            if ((int) $part->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }
            if ((int) $part->area_id !== (int) $user?->area_id) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $part->load(['area', 'operations', 'reasons']);

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

                $part->operations()->sync($request->validated('operation_id') ?? []);
                $part->reasons()->sync($request->validated('reason_id') ?? []);
            });

            $part->refresh()->load(['area', 'operations', 'reasons']);

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

    public function partSetstatus(Part $part)
    {
        try {
            if (! in_array((int) $part->status, [1, 99], true)) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['Part status must be 1 or 99 to be toggled.'],
                ], 400);
            }

            $part->update([
                'status' => (int) $part->status === 99 ? 1 : 99,
            ]);

            $part->refresh()->load('area');

            return ApiResponseHelper::success('Part status updated successfully', PartDataHelper::transform($part));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update part status');
        }
    }
}
