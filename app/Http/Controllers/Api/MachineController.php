<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\MachineDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMachineRequest;
use App\Http\Requests\Api\UpdateMachineRequest;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');

            $machinesQuery = Machine::query()
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

            $machines = $machinesQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $machines->getCollection()->map(
                fn (Machine $machine) => MachineDataHelper::transform($machine)
            )->all(), [
                'current_page' => $machines->currentPage(),
                'last_page' => $machines->lastPage(),
                'per_page' => $machines->perPage(),
                'total' => $machines->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machines');
        }
    }

    public function store(StoreMachineRequest $request)
    {
        try {
            $machine = DB::transaction(function () use ($request) {
                return Machine::create([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'image' => $request->input('image'),
                    'image_side' => $request->input('image_side'),
                    'status' => $request->integer('status'),
                ]);
            });

            return ApiResponseHelper::success('Machine created successfully', MachineDataHelper::transform($machine), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create machine');
        }
    }

    public function show(Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            return ApiResponseHelper::success('Data retrieved successfully', MachineDataHelper::transform($machine));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine');
        }
    }

    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Machine has been deleted and cannot be updated.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $machine) {
                $machine->update([
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'image' => $request->input('image'),
                    'image_side' => $request->input('image_side'),
                    'status' => $request->integer('status'),
                ]);
            });

            $machine->refresh();

            return ApiResponseHelper::success('Machine updated successfully', MachineDataHelper::transform($machine));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update machine');
        }
    }

    public function destroy(Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Machine has already been deleted.'],
                ], 400);
            }

            $machine->update([
                'status' => 99,
            ]);

            return ApiResponseHelper::success('Machine deleted successfully', MachineDataHelper::transform($machine));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete machine');
        }
    }
}
