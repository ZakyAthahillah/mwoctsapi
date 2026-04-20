<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\ReasonDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReasonRequest;
use App\Http\Requests\Api\UpdateReasonRequest;
use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReasonController extends Controller
{
    public function reasonActive(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $divisionId = $request->query('division_id');

            $reasonsQuery = Reason::query()
                ->with(['area', 'division'])
                ->where('status', '<>', 11)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
                ->when($divisionId !== null && $divisionId !== '', fn ($query) => $query->where('division_id', $divisionId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $reasons = $reasonsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $reasons->getCollection()->map(
                fn (Reason $reason) => ReasonDataHelper::transform($reason)
            )->all(), [
                'current_page' => $reasons->currentPage(),
                'last_page' => $reasons->lastPage(),
                'per_page' => $reasons->perPage(),
                'total' => $reasons->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve active reasons');
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $divisionId = $request->query('division_id');

            $reasonsQuery = Reason::query()
                ->with(['area', 'division'])
                ->where('status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
                ->when($divisionId !== null && $divisionId !== '', fn ($query) => $query->where('division_id', $divisionId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $reasons = $reasonsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $reasons->getCollection()->map(
                fn (Reason $reason) => ReasonDataHelper::transform($reason)
            )->all(), [
                'current_page' => $reasons->currentPage(),
                'last_page' => $reasons->lastPage(),
                'per_page' => $reasons->perPage(),
                'total' => $reasons->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reasons');
        }
    }

    public function store(StoreReasonRequest $request)
    {
        try {
            $reason = DB::transaction(function () use ($request) {
                return Reason::create([
                    'area_id' => auth('api')->user()?->area_id,
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'division_id' => $request->input('division_id'),
                    'status' => $request->integer('status'),
                ]);
            });

            $reason->load(['area', 'division']);

            return ApiResponseHelper::success('Reason created successfully', ReasonDataHelper::transform($reason), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create reason');
        }
    }

    public function show(Reason $reason)
    {
        try {
            $user = auth('api')->user();
            if ((int) $reason->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }
            if ((int) $reason->area_id !== (int) $user?->area_id) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $reason->load(['area', 'division']);

            return ApiResponseHelper::success('Data retrieved successfully', ReasonDataHelper::transform($reason));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve reason');
        }
    }

    public function update(UpdateReasonRequest $request, Reason $reason)
    {
        try {
            if ((int) $reason->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Reason has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $reason) {
                $reason->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'division_id' => $request->input('division_id'),
                    'status' => $request->integer('status'),
                ]);
            });

            $reason->refresh()->load(['area', 'division']);

            return ApiResponseHelper::success('Reason updated successfully', ReasonDataHelper::transform($reason));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update reason');
        }
    }

    public function destroy(Reason $reason)
    {
        try {
            if ((int) $reason->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Reason has already been deleted.'],
                ], 400);
            }

            $reason->update([
                'status' => 99,
            ]);

            $reason->refresh()->load(['area', 'division']);

            return ApiResponseHelper::success('Reason deleted successfully', ReasonDataHelper::transform($reason));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete reason');
        }
    }

    public function reasonSetstatus(Reason $reason)
    {
        try {
            if (! in_array((int) $reason->status, [1, 99], true)) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['Reason status must be 1 or 99 to be toggled.'],
                ], 400);
            }

            $reason->update([
                'status' => (int) $reason->status === 99 ? 1 : 99,
            ]);

            $reason->refresh()->load(['area', 'division']);

            return ApiResponseHelper::success('Reason status updated successfully', ReasonDataHelper::transform($reason));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update reason status');
        }
    }
}
