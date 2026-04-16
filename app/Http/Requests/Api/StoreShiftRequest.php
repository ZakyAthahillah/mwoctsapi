<?php

namespace App\Http\Requests\Api;

class StoreShiftRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:100'],
            'time_start' => ['present', 'nullable', 'date_format:H:i'],
            'time_finish' => ['present', 'nullable', 'date_format:H:i'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
