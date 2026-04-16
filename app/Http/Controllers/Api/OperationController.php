<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\OperationDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOperationRequest;
use App\Http\Requests\Api\UpdateOperationRequest;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $operationsQuery = Operation::query()
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

            $operations = $operationsQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $operations->getCollection()->map(
                fn (Operation $operation) => OperationDataHelper::transform($operation)
            )->all(), [
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve operations');
        }
    }

    public function store(StoreOperationRequest $request)
    {
        try {
            $operation = DB::transaction(function () use ($request) {
                return Operation::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $operation->load('area');

            return ApiResponseHelper::success('Operation created successfully', OperationDataHelper::transform($operation), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create operation');
        }
    }

    public function show(Operation $operation)
    {
        try {
            if ((int) $operation->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $operation->load('area');

            return ApiResponseHelper::success('Data retrieved successfully', OperationDataHelper::transform($operation));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve operation');
        }
    }

    public function update(UpdateOperationRequest $request, Operation $operation)
    {
        try {
            if ((int) $operation->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Operation has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $operation) {
                $operation->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'status' => $request->integer('status'),
                ]);
            });

            $operation->refresh()->load('area');

            return ApiResponseHelper::success('Operation updated successfully', OperationDataHelper::transform($operation));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update operation');
        }
    }

    public function destroy(Operation $operation)
    {
        try {
            if ((int) $operation->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Operation has already been deleted.'],
                ], 400);
            }

            $operation->update([
                'status' => 99,
            ]);

            $operation->refresh()->load('area');

            return ApiResponseHelper::success('Operation deleted successfully', OperationDataHelper::transform($operation));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete operation');
        }
    }
}
