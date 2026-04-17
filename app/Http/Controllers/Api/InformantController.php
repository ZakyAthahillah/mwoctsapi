<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\InformantDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInformantRequest;
use App\Http\Requests\Api\UpdateInformantRequest;
use App\Models\Informant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformantController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');
            $groupId = $request->query('group_id');

            $informantsQuery = Informant::query()
                ->with(['area', 'group'])
                ->where('status', '<>', 99)
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($groupId !== null && $groupId !== '', fn ($query) => $query->where('group_id', $groupId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('id', 'desc');

            $informants = $informantsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $informants->getCollection()->map(
                fn (Informant $informant) => InformantDataHelper::transform($informant)
            )->all(), [
                'current_page' => $informants->currentPage(),
                'last_page' => $informants->lastPage(),
                'per_page' => $informants->perPage(),
                'total' => $informants->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve informants');
        }
    }

    public function store(StoreInformantRequest $request)
    {
        try {
            $informant = DB::transaction(function () use ($request) {
                return Informant::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                    'group_id' => $request->input('group_id'),
                ]);
            });

            $informant->load(['area', 'group']);

            return ApiResponseHelper::success('Informant created successfully', InformantDataHelper::transform($informant), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create informant');
        }
    }

    public function show(Informant $informant)
    {
        try {
            if ((int) $informant->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $informant->load(['area', 'group']);

            return ApiResponseHelper::success('Data retrieved successfully', InformantDataHelper::transform($informant));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve informant');
        }
    }

    public function update(UpdateInformantRequest $request, Informant $informant)
    {
        try {
            if ((int) $informant->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Informant has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $informant) {
                $informant->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                    'group_id' => $request->input('group_id'),
                ]);
            });

            $informant->refresh()->load(['area', 'group']);

            return ApiResponseHelper::success('Informant updated successfully', InformantDataHelper::transform($informant));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update informant');
        }
    }

    public function destroy(Informant $informant)
    {
        try {
            if ((int) $informant->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Informant has already been deleted.'],
                ], 400);
            }

            $informant->update([
                'status' => 99,
            ]);

            $informant->refresh()->load(['area', 'group']);

            return ApiResponseHelper::success('Informant deleted successfully', InformantDataHelper::transform($informant));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete informant');
        }
    }
}
