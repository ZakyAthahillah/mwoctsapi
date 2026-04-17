<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\SerialNumberDataHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSerialNumberRequest;
use App\Http\Requests\Api\UpdateSerialNumberFirstRequest;
use App\Http\Requests\Api\UpdateSerialNumberRequest;
use App\Models\PartSerialNumber;
use App\Models\SerialNumber;
use App\Models\SerialNumberLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SerialNumberController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->integer('per_page', 10);
            $perPage = max(1, min($perPage, 100));
            $search = trim((string) $request->query('search', ''));
            $areaId = $request->query('area_id');
            $machineId = $request->query('machine_id');
            $positionId = $request->query('position_id');
            $partId = $request->query('part_id');

            $serialNumbersQuery = SerialNumber::query()
                ->with(['area', 'machine', 'position', 'part', 'partSerialNumber'])
                ->when($areaId !== null && $areaId !== '', fn ($query) => $query->where('area_id', $areaId))
                ->when($machineId !== null && $machineId !== '', fn ($query) => $query->where('machine_id', $machineId))
                ->when($positionId !== null && $positionId !== '', fn ($query) => $query->where('position_id', $positionId))
                ->when($partId !== null && $partId !== '', fn ($query) => $query->where('part_id', $partId))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->whereHas('partSerialNumber', fn ($partSerialNumberQuery) => $partSerialNumberQuery->where('serial_number', 'like', '%'.$search.'%'))
                            ->orWhereHas('part', fn ($partQuery) => $partQuery->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('machine', fn ($machineQuery) => $machineQuery->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('position', fn ($positionQuery) => $positionQuery->where('name', 'like', '%'.$search.'%'));
                    });
                })
                ->orderBy('id', 'desc');

            $serialNumbers = $serialNumbersQuery->paginate($perPage)->appends($request->query());

            return ApiResponseHelper::success('Data retrieved successfully', $serialNumbers->getCollection()->map(
                fn (SerialNumber $serialNumber) => SerialNumberDataHelper::transform($serialNumber)
            )->all(), [
                'current_page' => $serialNumbers->currentPage(),
                'last_page' => $serialNumbers->lastPage(),
                'per_page' => $serialNumbers->perPage(),
                'total' => $serialNumbers->total(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve serial numbers');
        }
    }

    public function store(StoreSerialNumberRequest $request)
    {
        try {
            $user = auth('api')->user();
            $partSerialNumber = PartSerialNumber::query()->findOrFail($request->integer('part_serial_number_id'));

            if ($partSerialNumber->part_id !== $request->integer('part_id')) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Selected part serial number does not belong to the selected part.'],
                ], 400);
            }

            $duplicateSerialNumber = SerialNumber::query()
                ->where('area_id', $request->integer('area_id'))
                ->where('part_serial_number_id', $request->integer('part_serial_number_id'))
                ->where('part_id', $request->integer('part_id'))
                ->first();

            if ($duplicateSerialNumber !== null) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Serial number is already used in another machine or position within the selected area.'],
                ], 400);
            }

            $duplicateAssignment = SerialNumber::query()
                ->where('area_id', $request->integer('area_id'))
                ->where('machine_id', $request->integer('machine_id'))
                ->where('position_id', $request->integer('position_id'))
                ->where('part_id', $request->integer('part_id'))
                ->first();

            if ($duplicateAssignment !== null) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Selected machine, position, and part already have a serial number.'],
                ], 400);
            }

            $serialNumber = DB::transaction(function () use ($request, $user) {
                $serialNumber = SerialNumber::create([
                    'area_id' => $request->integer('area_id'),
                    'machine_id' => $request->integer('machine_id'),
                    'position_id' => $request->integer('position_id'),
                    'part_id' => $request->integer('part_id'),
                    'part_serial_number_id' => $request->integer('part_serial_number_id'),
                ]);

                SerialNumberLog::create([
                    'area_id' => $serialNumber->area_id,
                    'machine_id' => $serialNumber->machine_id,
                    'position_id' => $serialNumber->position_id,
                    'part_id' => $serialNumber->part_id,
                    'part_serial_number_id' => $serialNumber->part_serial_number_id,
                    'updatedBy' => $user?->id,
                    'updatedDate' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'action' => 1,
                ]);

                return $serialNumber;
            });

            $serialNumber->load(['area', 'machine', 'position', 'part', 'partSerialNumber']);

            return ApiResponseHelper::success('Serial number created successfully', SerialNumberDataHelper::transform($serialNumber), null, 201);
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to create serial number');
        }
    }

    public function show(SerialNumber $serialNumber)
    {
        try {
            $serialNumber->load(['area', 'machine', 'position', 'part', 'partSerialNumber']);

            return ApiResponseHelper::success('Data retrieved successfully', SerialNumberDataHelper::transform($serialNumber));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve serial number');
        }
    }

    public function update(UpdateSerialNumberRequest $request, SerialNumber $serialNumber)
    {
        try {
            $user = auth('api')->user();
            $partSerialNumber = PartSerialNumber::query()->findOrFail($request->integer('part_serial_number_id'));

            if ($partSerialNumber->part_id !== $serialNumber->part_id) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Selected part serial number does not belong to the same part as this assignment.'],
                ], 400);
            }

            $duplicateSerialNumber = SerialNumber::query()
                ->where('area_id', $serialNumber->area_id)
                ->where('part_serial_number_id', $request->integer('part_serial_number_id'))
                ->where('part_id', $serialNumber->part_id)
                ->where('id', '!=', $serialNumber->id)
                ->first();

            if ($duplicateSerialNumber !== null) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Serial number is already used in another machine or position within the selected area.'],
                ], 400);
            }

            DB::transaction(function () use ($request, $serialNumber, $user) {
                $oldPartSerialNumberId = $serialNumber->part_serial_number_id;

                $serialNumber->update([
                    'part_serial_number_id' => $request->integer('part_serial_number_id'),
                ]);

                if ($oldPartSerialNumberId !== $serialNumber->part_serial_number_id) {
                    SerialNumberLog::create([
                        'area_id' => $serialNumber->area_id,
                        'machine_id' => $serialNumber->machine_id,
                        'position_id' => $serialNumber->position_id,
                        'part_id' => $serialNumber->part_id,
                        'part_serial_number_id' => $serialNumber->part_serial_number_id,
                        'updatedBy' => $user?->id,
                        'updatedDate' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'action' => 2,
                    ]);
                }
            });

            $serialNumber->refresh()->load(['area', 'machine', 'position', 'part', 'partSerialNumber']);

            return ApiResponseHelper::success('Serial number updated successfully', SerialNumberDataHelper::transform($serialNumber));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update serial number');
        }
    }

    public function first(PartSerialNumber $partSerialNumber)
    {
        try {
            $partSerialNumber->load(['part', 'area']);

            $logFirst = $partSerialNumber->logs()
                ->with(['machine', 'position', 'updatedByUser'])
                ->whereIn('action', [1, 2])
                ->orderBy('id', 'desc')
                ->first();

            return ApiResponseHelper::success('Data retrieved successfully', SerialNumberDataHelper::transformFirst($partSerialNumber, $logFirst));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to retrieve initial serial number assignment');
        }
    }

    public function updateFirst(UpdateSerialNumberFirstRequest $request, PartSerialNumber $partSerialNumber)
    {
        try {
            if ((int) $partSerialNumber->status !== 1) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Part serial number is not active and cannot be assigned.'],
                ], 400);
            }

            $user = auth('api')->user();
            $partSerialNumber->load(['part', 'area']);

            if ($partSerialNumber->area_id !== null && $partSerialNumber->area_id !== $request->integer('area_id')) {
                return ApiResponseHelper::error('Bad request', [
                    'request' => ['Selected area does not match the part serial number area.'],
                ], 400);
            }

            $serialNumberLogNoFirst = $partSerialNumber->logs()
                ->where('action', '>', 2)
                ->first();

            if ($serialNumberLogNoFirst === null) {
                $existingSerialNumber = SerialNumber::query()
                    ->where('area_id', $request->integer('area_id'))
                    ->where('machine_id', $request->integer('machine_id'))
                    ->where('position_id', $request->integer('position_id'))
                    ->where('part_id', $partSerialNumber->part_id)
                    ->first();

                if ($existingSerialNumber !== null && $existingSerialNumber->part_serial_number_id !== $partSerialNumber->id) {
                    return ApiResponseHelper::error('Bad request', [
                        'request' => ['Selected machine, position, and part already have a serial number.'],
                    ], 400);
                }
            }

            $partSerialNumberCreateLog = $partSerialNumber->logs()
                ->where('action', 1)
                ->first();

            $result = DB::transaction(function () use ($request, $partSerialNumber, $partSerialNumberCreateLog, $serialNumberLogNoFirst, $user) {
                $action = $partSerialNumberCreateLog !== null ? 2 : 1;

                $log = SerialNumberLog::create([
                    'area_id' => $request->integer('area_id'),
                    'machine_id' => $request->integer('machine_id'),
                    'position_id' => $request->integer('position_id'),
                    'part_id' => $partSerialNumber->part_id,
                    'part_serial_number_id' => $partSerialNumber->id,
                    'updatedBy' => $user?->id,
                    'updatedDate' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'action' => $action,
                ]);

                if ($serialNumberLogNoFirst === null) {
                    SerialNumber::query()->updateOrCreate([
                        'area_id' => $request->integer('area_id'),
                        'machine_id' => $request->integer('machine_id'),
                        'position_id' => $request->integer('position_id'),
                        'part_id' => $partSerialNumber->part_id,
                    ], [
                        'part_serial_number_id' => $partSerialNumber->id,
                    ]);
                }

                return $log;
            });

            $result->load(['machine', 'position', 'updatedByUser']);
            $partSerialNumber->load(['part', 'area']);

            return ApiResponseHelper::success('Initial serial number assignment updated successfully', SerialNumberDataHelper::transformFirst($partSerialNumber, $result));
        } catch (\Throwable $exception) {
            return ApiResponseHelper::error('Failed to update initial serial number assignment');
        }
    }
}
