<?php

namespace App\Http\Requests\Api;

class UpdateSerialNumberFirstRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
        ];
    }
}
