<?php

namespace App\Http\Requests\Api;

class StoreSerialNumberRequest extends BaseApiFormRequest
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
            'part_id' => ['required', 'integer', 'exists:parts,id'],
            'part_serial_number_id' => ['required', 'integer', 'exists:part_serial_numbers,id'],
        ];
    }
}
