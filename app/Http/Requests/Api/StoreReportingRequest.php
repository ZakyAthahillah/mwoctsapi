<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreReportingRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $areaId = $this->authenticatedAreaId();

        return [
            'machine_id' => ['required', 'integer', Rule::exists('machines', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'part_id' => ['required', 'integer', Rule::exists('parts', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'part_serial_number_id' => ['nullable', 'integer', Rule::exists('part_serial_numbers', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'operation_id' => ['required', 'integer', Rule::exists('operations', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'reason_id' => ['required', 'integer', Rule::exists('reasons', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'informant_id' => ['required', 'integer', Rule::exists('informants', 'id')->where(fn ($query) => $areaId === null ? $query->whereNull('area_id') : $query->where('area_id', $areaId))],
            'reporting_type' => ['required', 'integer', 'between:1,99'],
            'reporting_date' => ['required', 'date'],
            'reporting_notes' => ['nullable', 'string'],
        ];
    }
}
