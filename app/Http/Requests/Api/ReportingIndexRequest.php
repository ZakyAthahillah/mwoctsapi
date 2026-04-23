<?php

namespace App\Http\Requests\Api;

class ReportingIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'division_id' => ['sometimes', 'integer'],
            'machine_id' => ['sometimes', 'integer'],
            'position_id' => ['sometimes', 'integer'],
            'part_id' => ['sometimes', 'integer'],
            'part_serial_number_id' => ['sometimes', 'integer'],
            'operation_id' => ['sometimes', 'integer'],
            'reason_id' => ['sometimes', 'integer'],
            'informant_id' => ['sometimes', 'integer'],
            'reporting_type' => ['sometimes', 'integer', 'between:0,99'],
            'status' => ['sometimes', 'integer', 'between:0,99'],
            'search' => ['sometimes', 'string'],
        ];
    }
}
