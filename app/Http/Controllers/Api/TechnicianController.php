<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\TechnicianDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTechnicianRequest;
use App\Http\Requests\Api\UpdateTechnicianRequest;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');
            $divisionId = $request->query('division_id');
            $groupId = $request->query('group_id');

            $techniciansQuery = Technician::query()
                ->with(['area', 'division', 'group'])
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($divisionId !== null && $divisionId !== '', fn ($query) => $query->where('division_id', $divisionId))
                ->when($groupId !== null && $groupId !== '', fn ($query) => $query->where('group_id', $groupId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $technicians = $techniciansQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $technicians->getCollection()->map(
                fn (Technician $technician) => TechnicianDataHelper::transform($technician)
            )->all(), [
                'current_page' => $technicians->currentPage(),
                'last_page' => $technicians->lastPage(),
                'per_page' => $technicians->perPage(),
                'total' => $technicians->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve technicians');
        }
    }

    public function store(StoreTechnicianRequest $request)
    {
        try {
            $technician = DB::transaction(function () use ($request) {
                return Technician::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'division_id' => $request->input('division_id'),
                    'status' => $request->integer('status'),
                    'group_id' => $request->input('group_id'),
                ]);
            });

            $technician->load(['area', 'division', 'group']);

            return ApiResponseHelper::success('Technician created successfully', TechnicianDataHelper::transform($technician), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create technician');
        }
    }

    public function show(Technician $technician)
    {
        try {
            if ((int) $technician->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $technician->load(['area', 'division', 'group']);

            return ApiResponseHelper::success('Data retrieved successfully', TechnicianDataHelper::transform($technician));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve technician');
        }
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician)
    {
        try {
            if ((int) $technician->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Technician has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $technician) {
                $technician->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'division_id' => $request->input('division_id'),
                    'status' => $request->integer('status'),
                    'group_id' => $request->input('group_id'),
                ]);
            });

            $technician->refresh()->load(['area', 'division', 'group']);

            return ApiResponseHelper::success('Technician updated successfully', TechnicianDataHelper::transform($technician));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update technician');
        }
    }

    public function destroy(Technician $technician)
    {
        try {
            if ((int) $technician->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Technician has already been deleted.'],
                ], 400);
            }

            $technician->update([
                'status' => 99,
            ]);

            $technician->refresh()->load(['area', 'division', 'group']);

            return ApiResponseHelper::success('Technician deleted successfully', TechnicianDataHelper::transform($technician));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete technician');
        }
    }
}
