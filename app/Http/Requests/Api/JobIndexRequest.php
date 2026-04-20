<?php

namespace App\Http\Requests\Api;

class JobIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:new,on_progress,onProgress,extend,waiting_for_approval,approval,finish'],
            'division_id' => ['sometimes', 'integer'],
            'machine_id' => ['sometimes', 'integer'],
            'position_id' => ['sometimes', 'integer'],
            'part_id' => ['sometimes', 'integer'],
            'operation_id' => ['sometimes', 'integer'],
            'reason_id' => ['sometimes', 'integer'],
            'informant_id' => ['sometimes', 'integer'],
            'reporting_type' => ['sometimes', 'integer'],
            'technician_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string'],
        ];
    }
}
