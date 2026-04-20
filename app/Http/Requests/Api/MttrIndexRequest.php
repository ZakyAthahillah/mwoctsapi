<?php

namespace App\Http\Requests\Api;

class MttrIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:yearly,monthly,daily,shift'],
            'year' => ['required_unless:type,yearly,shift', 'nullable', 'integer'],
            'month' => ['required_if:type,daily', 'nullable', 'integer', 'between:1,12'],
            'period_start' => ['required_if:type,shift', 'nullable', 'date'],
            'period_end' => ['required_if:type,shift', 'nullable', 'date'],
            'machine_id' => ['sometimes', 'integer'],
            'position_id' => ['sometimes', 'integer'],
            'part_id' => ['sometimes', 'integer'],
            'operation_id' => ['sometimes', 'integer'],
        ];
    }
}
