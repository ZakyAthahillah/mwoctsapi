<?php

namespace App\Http\Requests\Api;

class JobFinishRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'processing_date_finish' => ['required', 'date'],
            'operation_id_actual' => ['required', 'integer'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_change_part' => ['sometimes', 'boolean'],
            'part_serial_number_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
