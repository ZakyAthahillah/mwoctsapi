<?php

namespace App\Http\Requests\Api;

class ReportingReportIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'part_id' => ['nullable', 'integer', 'exists:parts,id'],
            'part_serial_number_id' => ['nullable', 'integer', 'exists:part_serial_numbers,id'],
            'operation_id' => ['nullable', 'integer', 'exists:operations,id'],
            'operation_id_actual' => ['nullable', 'integer', 'exists:operations,id'],
            'reason_id' => ['nullable', 'integer', 'exists:reasons,id'],
            'informant_id' => ['nullable', 'integer', 'exists:informants,id'],
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'reporting_type' => ['nullable', 'integer', 'between:0,99'],
            'status' => ['nullable', 'integer', 'between:0,99'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
