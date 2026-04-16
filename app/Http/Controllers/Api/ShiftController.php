<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\ShiftDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShiftRequest;
use App\Http\Requests\Api\UpdateShiftRequest;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $shiftsQuery = Shift::query()
                ->with('area')
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%');
                })
                ->orderBy('id', 'desc');

            $shifts = $shiftsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $shifts->getCollection()->map(
                fn (Shift $shift) => ShiftDataHelper::transform($shift)
            )->all(), [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve shifts');
        }
    }

    public function store(StoreShiftRequest $request)
    {
        try {
            $shift = DB::transaction(function () use ($request) {
                return Shift::create([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'time_start' => $request->input('time_start') !== null ? $request->input('time_start').':00' : null,
                    'time_finish' => $request->input('time_finish') !== null ? $request->input('time_finish').':00' : null,
                    'status' => $request->integer('status'),
                ]);
            });

            $shift->load('area');

            return ApiResponseHelper::success('Shift created successfully', ShiftDataHelper::transform($shift), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create shift');
        }
    }

    public function show(Shift $shift)
    {
        try {
            if ((int) $shift->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $shift->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', ShiftDataHelper::transform($shift));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve shift');
        }
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        try {
            if ((int) $shift->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Shift has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $shift) {
                $shift->update([
                    'area_id' => $request->input('area_id'),
                    'name' => $request->string('name')->toString(),
                    'time_start' => $request->input('time_start') !== null ? $request->input('time_start').':00' : null,
                    'time_finish' => $request->input('time_finish') !== null ? $request->input('time_finish').':00' : null,
                    'status' => $request->integer('status'),
                ]);
            });

            $shift->refresh()->load('area');

            return ApiResponseHelper::success('Shift updated successfully', ShiftDataHelper::transform($shift));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update shift');
        }
    }

    public function destroy(Shift $shift)
    {
        try {
            if ((int) $shift->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Shift has already been deleted.'],
                ], 400);
            }

            $shift->update([
                'status' => 99,
            ]);

            $shift->refresh()->load('area');

            return ApiResponseHelper::success('Shift deleted successfully', ShiftDataHelper::transform($shift));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete shift');
        }
    }
}
