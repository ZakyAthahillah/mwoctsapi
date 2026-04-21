<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\MachineDataHelper;
use App\Helpers\MachineImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMachineRequest;
use App\Http\Requests\Api\UpdateMachineRequest;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    public function machineActive(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $machinesQuery = Machine::query()
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
            return ApiResponseHelper::error('Failed to retrieve active machines');
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));

            $machinesQuery = Machine::query()
                ->with('area')
                ->whereIn('status', [1, 99])
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id), fn ($query) => $query->whereNull('area_id'))
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
                $positionIds = $request->validated('position_id', []);

                $machine = Machine::create([
                    'area_id' => auth('api')->user()?->area_id,
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'image' => null,
                    'image_side' => null,
                    'status' => $request->integer('status'),
                ]);

                DB::table('machine_progress')->insert([
                    'machine_id' => $machine->id,
                    'data' => 1,
                    'position' => $positionIds !== [] ? 1 : 0,
                    'operation' => 0,
                    'reason' => 0,
                    'image' => 0,
                    'part' => 0,
                ]);

                if ($positionIds !== []) {
                    $machine->positions()->sync($positionIds);
                }

                return $machine;
            });

            $imagePayload = MachineImageHelper::resolveImageUrls($request, $machine);

            if ($imagePayload !== []) {
                $machine->update($imagePayload);
                DB::table('machine_progress')
                    ->where('machine_id', $machine->id)
                    ->update([
                        'image' => ! empty($imagePayload['image']) && ! empty($imagePayload['image_side']) ? 1 : 0,
                    ]);
            }

            $machine->load('area');

            return ApiResponseHelper::success('Machine created successfully', MachineDataHelper::transform($machine), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create machine');
        }
    }

    public function show(Machine $machine)
    {
        try {
            $user = auth('api')->user();
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }
            if ((int) $machine->area_id !== (int) $user?->area_id) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $machine->load('area');

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
                $imagePayload = MachineImageHelper::resolveImageUrls($request, $machine);

                $machinePayload = [
                    'area_id' => $request->input('area_id'),
                    'code' => $request->string('code')->toString(),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'status' => $request->integer('status'),
                ];

                if ($request->exists('image')) {
                    $machinePayload['image'] = $imagePayload['image'] ?? null;
                }

                if ($request->exists('image_side')) {
                    $machinePayload['image_side'] = $imagePayload['image_side'] ?? null;
                }

                $machine->update($machinePayload);

                if ($request->exists('position_id') || $request->exists('position_ids')) {
                    $positionIds = $request->validated('position_id', []);

                    $machine->positions()->sync($positionIds);

                    DB::table('machine_progress')
                        ->where('machine_id', $machine->id)
                        ->update([
                            'position' => $positionIds !== [] ? 1 : 0,
                        ]);
                }

                if ($request->has('parts.id')) {
                    $partIds = $request->input('parts.id', []);
                    $xs = $request->input('parts.x', []);
                    $ys = $request->input('parts.y', []);
                    $xsSide = $request->input('parts.x_side', []);
                    $ysSide = $request->input('parts.y_side', []);

                    $machine->parts()->delete();
                    $machine->partsSide()->delete();

                    foreach ($partIds as $index => $partId) {
                        $machine->parts()->create([
                            'part_id' => $partId,
                            'sort_order' => $index + 1,
                            'pos_x' => $xs[$index] ?? 0,
                            'pos_y' => $ys[$index] ?? 0,
                        ]);

                        $machine->partsSide()->create([
                            'part_id' => $partId,
                            'sort_order' => $index + 1,
                            'pos_x' => $xsSide[$index] ?? 0,
                            'pos_y' => $ysSide[$index] ?? 0,
                        ]);
                    }

                    DB::table('machine_progress')
                        ->where('machine_id', $machine->id)
                        ->update([
                            'part' => count($partIds) > 0 ? 1 : 0,
                        ]);
                }
            });

            $machine->refresh()->load('area');

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

            $machine->refresh()->load('area');

            return ApiResponseHelper::success('Machine deleted successfully', MachineDataHelper::transform($machine));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to delete machine');
        }
    }

    public function activate(Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Machine has been deleted and cannot be activated.'],
                ], 400);
            }

            $progress = DB::table('machine_progress')
                ->where('machine_id', $machine->id)
                ->first();

            $totalProgress = $progress === null
                ? 0
                : (((int) $progress->data + (int) $progress->position + (int) $progress->image + (int) $progress->part) / 4) * 100;

            if ($totalProgress < 100) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Machine progress must be 100 before activation.'],
                ], 400);
            }

            $machine->update([
                'status' => (int) $machine->status === 1 ? 0 : 1,
            ]);

            $machine->refresh()->load('area');

            return ApiResponseHelper::success('Machine activation updated successfully', [
                ...MachineDataHelper::transform($machine),
                'progress' => (int) $totalProgress,
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update machine activation');
        }
    }

    public function getFullDataArray(Request $request)
    {
        try {
            $term = trim((string) ($request->query('term') ?? $request->input('term', '')));
            $user = $request->user();

            $machines = Machine::query()
                ->where('status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id))
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->where('code', 'like', '%'.$term.'%')
                            ->orWhere('name', 'like', '%'.$term.'%')
                            ->orWhere('description', 'like', '%'.$term.'%');
                    });
                })
                ->orderBy('name')
                ->get();

            return ApiResponseHelper::success('Data retrieved successfully', $machines->map(function (Machine $machine) {
                $parts = DB::table('machine_parts as machinePart')
                    ->join('parts as part', 'part.id', '=', 'machinePart.part_id')
                    ->where('machinePart.machine_id', $machine->id)
                    ->orderBy('machinePart.sort_order')
                    ->get([
                        'part.id',
                        'part.code',
                        'part.name',
                        'part.description',
                        'machinePart.pos_x',
                        'machinePart.pos_y',
                    ]);

                return [
                    'id' => (string) $machine->id,
                    'text' => trim(($machine->code ?? '').' : '.($machine->name ?? '')),
                    'code' => $machine->code,
                    'name' => $machine->name,
                    'description' => $machine->description,
                    'image' => $machine->image,
                    'parts' => $parts->map(fn ($part) => [
                        'id' => (string) $part->id,
                        'code' => $part->code,
                        'name' => $part->name,
                        'description' => $part->description,
                        'x' => $part->pos_x,
                        'y' => $part->pos_y,
                    ])->values()->all(),
                ];
            })->values()->all());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine options');
        }
    }

    public function getFullDataArrayJob(Request $request)
    {
        try {
            $term = trim((string) ($request->query('term') ?? $request->input('term', '')));
            $user = $request->user();

            $machines = Machine::query()
                ->where('status', 1)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id))
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->where('code', 'like', '%'.$term.'%')
                            ->orWhere('name', 'like', '%'.$term.'%')
                            ->orWhere('description', 'like', '%'.$term.'%');
                    });
                })
                ->orderBy('name')
                ->get();

            return ApiResponseHelper::success('Data retrieved successfully', $machines->map(function (Machine $machine) {
                $parts = DB::table('machine_parts as machinePart')
                    ->join('parts as part', 'part.id', '=', 'machinePart.part_id')
                    ->where('machinePart.machine_id', $machine->id)
                    ->orderBy('machinePart.sort_order')
                    ->get([
                        'part.id',
                        'part.code',
                        'part.name',
                        'part.description',
                        'machinePart.pos_x',
                        'machinePart.pos_y',
                    ]);

                return [
                    'id' => (string) $machine->id,
                    'text' => trim(($machine->code ?? '').' : '.($machine->name ?? '')),
                    'code' => $machine->code,
                    'name' => $machine->name,
                    'description' => $machine->description,
                    'image' => $machine->image,
                    'parts' => $parts->map(fn ($part) => [
                        'id' => (string) $part->id,
                        'code' => $part->code,
                        'name' => $part->name,
                        'description' => $part->description,
                        'x' => $part->pos_x,
                        'y' => $part->pos_y,
                    ])->values()->all(),
                ];
            })->values()->all());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve active machine options');
        }
    }

    public function getDetail(Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $machine->load('area');

            $parts = DB::table('machine_parts as machinePart')
                ->join('parts as part', 'part.id', '=', 'machinePart.part_id')
                ->where('machinePart.machine_id', $machine->id)
                ->orderBy('machinePart.sort_order')
                ->get([
                    'part.id',
                    'part.code',
                    'part.name',
                    'part.description',
                    'machinePart.pos_x',
                    'machinePart.pos_y',
                ]);

            $partsSide = DB::table('machine_part_sides as machinePartSide')
                ->join('parts as part', 'part.id', '=', 'machinePartSide.part_id')
                ->where('machinePartSide.machine_id', $machine->id)
                ->orderBy('machinePartSide.sort_order')
                ->get([
                    'part.id',
                    'part.code',
                    'part.name',
                    'part.description',
                    'machinePartSide.pos_x',
                    'machinePartSide.pos_y',
                ]);

            return ApiResponseHelper::success('Data retrieved successfully', [
                ...MachineDataHelper::transform($machine),
                'text' => trim(($machine->code ?? '').' : '.($machine->name ?? '')),
                'parts' => $parts->map(fn ($part) => [
                    'id' => (string) $part->id,
                    'code' => $part->code,
                    'name' => $part->name,
                    'description' => $part->description,
                    'x' => $part->pos_x,
                    'y' => $part->pos_y,
                ])->values()->all(),
                'parts_side' => $partsSide->map(fn ($part) => [
                    'id' => (string) $part->id,
                    'code' => $part->code,
                    'name' => $part->name,
                    'description' => $part->description,
                    'x' => $part->pos_x,
                    'y' => $part->pos_y,
                ])->values()->all(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine detail');
        }
    }

    public function getDetailJob(Request $request, string $machineId, string $positionId)
    {
        try {
            $user = $request->user();

            $machine = Machine::query()
                ->where('status', 1)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id))
                ->find($machineId);

            if ($machine === null) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $machine->load('area');

            $parts = DB::table('machine_parts as machinePart')
                ->join('parts as part', 'part.id', '=', 'machinePart.part_id')
                ->leftJoin('machine_position_parts as machinePositionPart', function ($join) use ($positionId, $machine) {
                    $join->on('machinePositionPart.part_id', '=', 'machinePart.part_id')
                        ->where('machinePositionPart.machine_id', '=', $machine->id)
                        ->where('machinePositionPart.position_id', '=', $positionId);
                })
                ->where('machinePart.machine_id', $machine->id)
                ->orderBy('machinePart.sort_order')
                ->get([
                    'part.id',
                    'part.code',
                    'part.name',
                    'part.description',
                    'machinePart.pos_x',
                    'machinePart.pos_y',
                    'machinePositionPart.serial_number',
                ]);

            $partsSide = DB::table('machine_part_sides as machinePartSide')
                ->join('parts as part', 'part.id', '=', 'machinePartSide.part_id')
                ->leftJoin('machine_position_parts as machinePositionPart', function ($join) use ($positionId, $machine) {
                    $join->on('machinePositionPart.part_id', '=', 'machinePartSide.part_id')
                        ->where('machinePositionPart.machine_id', '=', $machine->id)
                        ->where('machinePositionPart.position_id', '=', $positionId);
                })
                ->where('machinePartSide.machine_id', $machine->id)
                ->orderBy('machinePartSide.sort_order')
                ->get([
                    'part.id',
                    'part.code',
                    'part.name',
                    'part.description',
                    'machinePartSide.pos_x',
                    'machinePartSide.pos_y',
                    'machinePositionPart.serial_number',
                ]);

            return ApiResponseHelper::success('Data retrieved successfully', [
                ...MachineDataHelper::transform($machine),
                'text' => trim(($machine->code ?? '').' : '.($machine->name ?? '')),
                'parts' => $parts->map(fn ($part) => [
                    'id' => (string) $part->id,
                    'code' => $part->code,
                    'name' => $part->name,
                    'description' => $part->description,
                    'x' => $part->pos_x,
                    'y' => $part->pos_y,
                    'serial_number' => $part->serial_number,
                ])->values()->all(),
                'parts_side' => $partsSide->map(fn ($part) => [
                    'id' => (string) $part->id,
                    'code' => $part->code,
                    'name' => $part->name,
                    'description' => $part->description,
                    'x' => $part->pos_x,
                    'y' => $part->pos_y,
                    'serial_number' => $part->serial_number,
                ])->values()->all(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine job detail');
        }
    }

    public function getPosition(Request $request, Machine $machine)
    {
        try {
            if ((int) $machine->status === 99) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $term = trim((string) ($request->query('term') ?? $request->input('term', '')));
            $selected = (string) ($request->query('selected') ?? $request->input('selected', ''));

            $positions = DB::table('machine_position as machinePosition')
                ->join('positions as position', 'position.id', '=', 'machinePosition.position_id')
                ->where('machinePosition.machine_id', $machine->id)
                ->where('position.status', '<>', 99)
                ->when($term !== '', fn ($query) => $query->where('position.name', 'like', '%'.$term.'%'))
                ->orderBy('position.name')
                ->get([
                    'position.id',
                    'position.name',
                ]);

            return ApiResponseHelper::success('Data retrieved successfully', $positions->map(fn ($position) => [
                'id' => (string) $position->id,
                'text' => $position->name,
                'selected' => $selected !== '' && $selected === (string) $position->id,
            ])->values()->all());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine positions');
        }
    }

    public function getPart(Request $request, string $machineId, string $positionId)
    {
        try {
            $user = $request->user();

            $machine = Machine::query()
                ->where('status', '<>', 99)
                ->when($user?->area_id !== null, fn ($query) => $query->where('area_id', $user->area_id))
                ->find($machineId);

            if ($machine === null) {
                return ApiResponseHelper::error('Resource not found', null, 404);
            }

            $parts = DB::table('machine_parts as machinePart')
                ->join('parts as part', 'part.id', '=', 'machinePart.part_id')
                ->leftJoin('machine_position_parts as machinePositionPart', function ($join) use ($positionId, $machine) {
                    $join->on('machinePositionPart.part_id', '=', 'machinePart.part_id')
                        ->where('machinePositionPart.machine_id', '=', $machine->id)
                        ->where('machinePositionPart.position_id', '=', $positionId);
                })
                ->where('machinePart.machine_id', $machine->id)
                ->orderBy('machinePart.sort_order')
                ->get([
                    'part.id',
                    'part.name',
                    'machinePositionPart.serial_number',
                ]);

            return ApiResponseHelper::success('Data retrieved successfully', $parts->map(function ($part) {
                $text = $part->name;
                if ($part->serial_number !== null && $part->serial_number !== '') {
                    $text .= ' ('.$part->serial_number.')';
                }

                return [
                    'id' => (string) $part->id,
                    'text' => $text,
                    'serial_number' => $part->serial_number,
                ];
            })->values()->all());
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve machine parts');
        }
    }

    public function machineSetstatus(Machine $machine)
    {
        try {
            if (! in_array((int) $machine->status, [1, 99], true)) {
                return ApiResponseHelper::error('Bad request', [
                    'status' => ['Machine status must be 1 or 99 to be toggled.'],
                ], 400);
            }

            $machine->update([
                'status' => (int) $machine->status === 99 ? 1 : 99,
            ]);

            $machine->refresh()->load('area');

            return ApiResponseHelper::success('Machine status updated successfully', MachineDataHelper::transform($machine));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update machine status');
        }
    }
}
